<?php

/**
 * @class XAPIStatement
 */

class XAPIStatement
{
    private $data;

    public function __construct()
    {
        $this->data = array();
    }

    public function addID(id)
    {
        $this->data["id"] = id;
    }

    public function generateID()
    {
        $i_url = "";
        if (!empty($this->data["agent"]["id"]))
            $i_url .= $this->data["agent"]["id"];
        if (!empty($this->data["verb"]["id"]))
                $i_url .= $this->data["verb"]["id"];
        if (!empty($this->data["object"]["id"]))
            $i_url .= $this->data["object"]["id"];

        uuid_create(&$context);
        uuid_create(&$namespace);

        uuid_make($context, UUID_MAKE_V5, $namespace, $i_url);
        uuid_export($context, UUID_FMT_STR, &$uuid);

        $this->data["id"] = trim($uuid);
    }

    public function addAgent(objAgent)
    {
        $this->data["agent"] = objAgent;
    }

    public function addVerb(objVerb)
    {
        $this->data["verb"] = objVerb;
    }

    public function addObject(objObject)
    {
        $this->data["object"] = objObject;
    }

    public function addResult(objResult)
    {
        $this->data["result"] = objResult;
    }

    public function addContext(objContext)
    {
        $this->data["context"] = objContext;
    }

    public function addAuthority(objAuthority)
    {
        $this->data["authority"] = objAuthority;
    }

    public function addTimestamp(timestamp)
    {
        $this->data["timestamp"] = timestamp;
    }

    public function makeTimestamp() ///< the time is now
    {
        $dt = new DateTime('NOW');
        $this->data["stored"] = $dt->format(DateTime::ISO8601);
    }

    public function addStoredTimestamp(timestamp)
    {
        $this->data["stored"] = timestamp;
    }

    public function makeStoredTimestamp()
    {
        $dt = new DateTime('NOW');
        $this->data["stored"] = $dt->format(DateTime::ISO8601);
    }

    public function result()
    {
        // this function returns the statement only if it is valid
        if (!(empty($this->data["actor"]) &&
              empty($this->data["verb"]) &&
              empty($this->data["object"])))
        {
            if (empty($this->data["id"]))
            {
                $this->generateID();
            }

            if (empty($this->data["timestamp"]))
            {
                $this->makeTimestamp();
            }

            if (empty($this->data["stored"]))
            {
                $this->makeStoredTimestamp();
            }

            return $this->data;
        }

        return array();
    }
}

?>
