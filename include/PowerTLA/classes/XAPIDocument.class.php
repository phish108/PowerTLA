<?php

class XAPIDocument extends Logger
{
    protected $type = "agents_profile";
    protected $agent;
    protected $agentHash;
    protected $activityId;
    protected $document;
    protected $stateId;

    public function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    /** *************************************
     * SUBCLASSES MUST IMPLEMENT THESE FUNCTIONS
     */
    protected function verifyActivityId()
    {
        return false;
    }

    protected function verifyStateId()
    {
        return false;
    }

    public function read()
    {
        // read should check if all parameters are valid reading the given API type
        return false;
    }

    protected function store()
    {}

    public function remove()
    {}

    /** *************************************
     * SUBCLASSES Don't need to implement these functions
     */
    public function setActivityId($activityId)
    {
        // verify that the activty exists
        if (isset($activityId) && !empty($activityId))
        {
            $this->activityId = $activityId;

            if ($this->verifyActivityId())
            {
                return true;
            }

            $this->activityId = null;
        }
        return false;
    }


    public function setType($type)
    {
        if (isset($type) &&
            ($type == "activities_state" ||
            $type == "activities_profile" ||
            $type == "agents_profile"))
        {
            $this->type = $type;
        }
    }

    protected function generateID()
    {
        $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0x0fff ) | 0x4000,
                        mt_rand( 0, 0x3fff ) | 0x8000,
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));

        $this->stateId = trim($uuid);
        return $this->stateId;
    }

    public function setAgent($agent)
    {
        if (isset($agent) && !empty($agent))
        {
            $this->agent = $agent;
            $this->agentHash = sha1($agent);
        }
    }

    public function setStateId($stateId)
    {
        if (isset($stateId) && !empty($stateId))
        {
            $this->stateId = $stateId;

            if ($this->verifyStateId())
            {
                return true;
            }

            $this->stateId = null;
        }
        return false;
    }

    public function create($document)
    {
        if (isset($document) && !empty($document))
        {
            if (!isset($this->agent))
            {
                // need to generate the agent object

            }
            $this->document = $document;
            $this->stateId = $this->generateID();
            $this->store();
        }
    }

    public function update($document)
    {
        if (isset($this->document) &&
            !empty($this->document) &&
            isset($document) &&
            !empty($document))
        {
            foreach ($document as $key => $value)
            {
                $this->document[$key] = $value;
            }
            $this->store();
        }
    }

    public function get()
    {
        return $this->document;
    }
}

?>
