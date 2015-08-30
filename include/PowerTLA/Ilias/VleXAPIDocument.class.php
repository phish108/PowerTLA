<?php
class VleXAPIDocument extends XAPIDocument
{
    protected function verifyActivityId()
    {
        global $ilDB;
        $r = $ilDB->queryF("SELECT id FROM pwrtla_xapistatements WHERE id = %s",
                               array("text"), array($this->activityId));

        if ($data = $ilDB->fetchAssoc($r) && $data["id"] == $this->activityId)
        {
            return true;
        }
        // unset activityId
        $this->activityId = null;
        return false;
    }

    protected function verifyStateId()
    {
        global $ilDB;
        $r = $ilDB->queryF("SELECT id FROM pwrtla_xapidocuments WHERE id = %s",
                               array("text"), array($this->activityId));
        if ($data = $ilDB->fetchAssoc($r) && $data["id"] == $this->stateId)
        {
            return true;
        }
        return false;
    }

    public function read()
    {
        global $ilDB;

        $q = "SELECT id, document, agent, agenthash, activityid FROM pwrtla_xapidocuments WHERE doctype = %s";
        $values = array($this->type);
        $types = array("text");
        $qa = array();

        if (isset($this->stateId))
        {
            array_push($qa, "id = %s");
            array_push($values, $this->stateId);
            array_push($types, "text");
        }
        if (isset($this->agentHash))
        {
            array_push($qa, "agenthash = %s");
            array_push($values, $this->agenthash);
            array_push($types, "text");
        }
        if (isset($this->activityId))
        {
            array_push($qa, "activityid = %s");
            array_push($values, $this->activityid);
            array_push($types, "text");
        }

        if (count($qa))
        {
            $q .= implode(" AND ", $qa);
            $r = $ilDB->queryF($q, $types, $values);
            $rData = array();

            while ($data = $ilDB->fetchAssoc($r))
            {
                $rData[$data["id"]] = array();
                if ($data["activityid"]) {

                    $rData[$data["id"]][$data["activityid"]] = array(
                        "document" => json_decode($data["document"]),
                        "agent"    => json_decode($data["agent"])
                    );
                }

                $tagent = $data["agent"];
                $thash = $data["agenthash"];
            }

            if (count($rData) == 1)
            {
                $this->agent = $tagent;
                $this->agentHash = $thash;
                foreach ($rData as $k => $v)
                {
                    $this->stateId = $k;
                    foreach ($v as $l => $w)
                    {
                        $this->activityId = $l;
                        $this->document = $w;
                    }
                }
            }
            else
            {
                $this->document = $rData;
            }
        }

        return false;
    }

    protected function store()
    {
        global $ilDB;
        if (!isset($this->stateId))
        {
            $this->generateID();
        }
        if (!isset($this->agent) ||
            !isset($this->agentHash) ||
            !isset($this->document) ||
            empty($this->document))
        {
            return false;
        }

        $values = array(
            "id" =>      array("text", $this->stateId),
            "doctype" => array("text", $this->type),
            "agent"   => array("text", $this->agent),
            "agenthash" => array("text", $this->agentHash),
            "document" =>  array("text", json_encode($this->document))
        );

        if ($this->type != "agents_profile")
        {
            if (!isset($this->activityId))
            {
                return false;
            }

            $values["activityid"] =  array("text", $this->activityId);
        }

        $ilDB->insert("pwrtla_xapidocuments", $values);

        return true;
    }

    public function remove()
    {
        global $ilDB;

    }
}

?>
