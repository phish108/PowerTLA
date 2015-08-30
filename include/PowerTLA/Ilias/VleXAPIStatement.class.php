<?php
class VleXAPIStatement extends XAPIStatement
{
    protected function findActivity()
    {
        global $ilDB;
        if (isset($since))
        {
            $r = $ilDB->queryF("SELECT statement FROM pwrtla_xapistatements WHERE id = %s AND stored > %s",
                               array("text", "integer"),
                               array($this->activityId, $this->since));
        }
        else
        {
            $r = $ilDB->queryF("SELECT statement FROM pwrtla_xapistatements WHERE id = %s",
                               array("text"), array($this->activityId));
        }
        if ($data = $ilDB->fetchAssoc($r))
        {
            $statement = json_decode($data["statement"]);
            $this->stored = $statement["stored"];

            return true;
        }
        return false;
    }

    protected function storeStatement()
    {
        global $ilDB;
        $ilDB->insert("pwrtla_xapistatements", array(
            "id" => array("text", $this->statement["id"]),
            "statement"  => array("text", json_encode($this->statement)),
            "stored"   => array("text", $this->stored),
            "user_id"     => array("text", $this->userid)
        ));
    }

    public function validateNewId()
    {
        global $ilDB;
        $r = $ilDB->queryF("SELECT id FROM pwrtla_xapistatements WHERE id = %s",
                           array("text"), array($this->statement["id"]));
        if ($data = $ilDB->fetchAssoc($r))
        {
            return false;
        }
        return true;
    }

    // must check if the user exists
    protected function validateAgent()
    {
        global $ilDB;

        $agent = $this->statement["actor"];

        if (array_key_exists("mbox", $agent))
        {
            $email = array_pop(explode(":", $array["mbox"]));
        }
        if (array_key_exists("openid", $agent))
        {
            // note if the itoken is not there, then it is
            // not our user and we will reject.

            // TODO we MUST check if the URL belongs to us, too

            $itoken = array_pop(explode("/", $array["openid"]));
        }
        if (array_key_exists("account", $agent) &&
            array_key_exists("homepage", $agent["account"]) &&
            !empty($agent["account"]["homepage"]))
        {
            $itoken = array_pop(explode("/", $array["account"]["homepage"]));
        }

        // ilObjUser should be present!
        if (isset($email) && !empty($email))
        {
            $r = $ilDB->queryF("SELECT usr_id FROM usr_data ".
                               "WHERE usr_id= %s", array("text"), array($email));

            if ($data = $ilDB->fetchAssoc($r))
            {
                $this->userid = $data["usr_id"];
                return true;
            }
        }

        if (isset($itoken) && !empty($itoken))
        {
            $r = $ilDB->queryF("SELECT user_id FROM pwrtla_usertokens ".
                               "WHERE user_id= %s", array("text"), array($email));

            if ($data = $ilDB->fetchAssoc($r))
            {
                $user = new ilObjUser();

                $user->setId($data["user_id"]);
                $user->read();
                if ($user->getEmail())
                {
                    $this->userid = $user->getId();
                    return true;
                }
            }
        }

        return false;
    }
}

?>
