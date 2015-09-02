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

    // generalise the processing into parent class.
    protected function storeStatement()
    {
        global $ilDB;

        $dbstatement = array(
            "id" => array("text", $this->statement["ID"]),
            "statement"  => array("text", json_encode($this->statement)),
            "user_id"     => array("text", $this->userid)
        );

        // note the stored time contains an ISO String

        // get the timestamp
        $ts = $this->statement["timestamp"];
        if (isset($ts) && !empty($ts))
        {
            $aTS = preg_split('/[\w:-]+/ ');
            // pop seconds
            array_pop($aTS);

            $dbstatement["year"]    = $aTS[0];
            $dbstatement["month"]   = $aTS[0] . $aTS[1];
            $dbstatement["day"]     = $aTS[0] . $aTS[1] . $aTS[2];
            $dbstatement["hour"]    = $aTS[3];
            $dbstatement["minute"]  = $aTS[4];
        }

        $ts = $this->statement["stored"];
        if (isset($ts) && !empty($ts))
        {
            $aTS = preg_split('/[\w:-.]+/ ');
            // pop milliseconds
            array_pop($aTS);
            $dbstatement["stored"]    = implode("", $aTS);
        }

        $dbstatement["verb_id"] = $this->statement["verb"]["id"];

        if (isset($this->statement["object"]))
        {
            $dbstatement["object_id"]  = $this->statement["object"]["id"];
        }

        if (isset($this->statement["actor"]))
        {
            if (isset($this->statement["actor"]["mbox"]))
            {
                $dbstatement["agent_id"] = $this->statement["actor"]["mbox"];
            }
            else if (isset($this->statement["actor"]["mboxsha1hash"]))
            {
                $dbstatement["agent_id"] = $this->statement["actor"]["mboxsha1hash"];
            }
            else if (isset($this->statement["actor"]["openid"]))
            {
                $dbstatement["agent_id"] = $this->statement["actor"]["openid"];
            }

            // TODO: implement all the other ID variants.
        }

        if (isset($this->statement["result"]))
        {
            if (isset($this->statement["result"]["score"]))
            {
                $dbstatement["score"] = $this->statement["result"]["score"];
            }

            if (isset($this->statement["result"]["duration"]))
            {
                $duration = $this->statement["result"]["duration"];
                $nDuration = 0;
                // process the duration string

                $dbstatement["duration"] = $nDuration;
            }
        }

        if (isset($this->statement["context"]))
        {
            if (isset($this->statement["context"]["registration"]))
            {
                $dbstatement["registration"] = $this->statement["context"]["registration"];
            }

        }

        $ilDB->insert("pwrtla_xapistatements", $dbstatement);
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
