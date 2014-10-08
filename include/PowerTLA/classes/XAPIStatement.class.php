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

    public function addID($id)
    {
        $this->data["id"] = $id;
    }

    public function generateID()
    {

        $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0x0fff ) | 0x4000,
                        mt_rand( 0, 0x3fff ) | 0x8000,
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));

        $this->data["id"] = trim($uuid);
        return $this->data["id"];
    }

    public function addAgent($objAgent)
    {
        $this->data["actor"] = $objAgent;
    }

    public function addVerb($objVerb)
    {
        $this->data["verb"] = $objVerb;
    }

    public function addObject($objObject)
    {
        $this->data["object"] = $objObject;
    }

    public function addResult($objResult)
    {
        $this->data["result"] = $objResult;
    }

    public function addContext($objContext)
    {
        if (!isset($this->data["context"]))
        {
            $this->data["context"] = array();
        }
        $this->data["context"] = array_merge($this->data["context"], $objContext);
    }

    public function addAuthority($objAuthority)
    {
        $this->data["authority"] = $objAuthority;
    }

    public function addTimestamp($timestamp)
    {
        $this->data["timestamp"] = $timestamp;
    }

    public function makeTimestamp() ///< the time is now
    {
        $dt = new DateTime('NOW');
        $this->data["stored"] = $dt->format(DateTime::ISO8601);
    }

    public function addStoredTimestamp($timestamp)
    {
        $this->data["stored"] = $timestamp;
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
