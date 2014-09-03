<?php

/**
* @class XAPIStatement
*/

class XAPIStatement
{
    private $agent;
    private $verb;
    private $object;
    private $result;
    private $id;

    public function __construct()
    {
        $this->agent = array();
        $this->verb  = array();
        $this->
    }

    public function addID(id)
    {}

    public function generateID()
    {}

    public function addAgent(id)
    {}

    public function addVerb(id)
    {}

    public function addObject(id)
    {}

    public function addTimestamp(time)
    {}

    public function makeTimestamp() ///< the time is now
    {}

    public function addStoredTimestamp()
    {}

    public function makeStoredTimestamp()
    {}

    public function addScore(score, type) ///< score - the score value, type - 'scaled', 'raw', 'min', 'max'
    {}

    public function addDuration(duration)
    {}

    public function addResponse(response)
    {}

    public function addSuccessState(success)
    {}

    public function addCompletedState(completed)
    {}

    public function addPlatform(platform)
    {}

    /**
     * Dictionaries
     */

    public function agentDictionary(dictObject)
    {}

    public function objectDictionary(dictObject)
    {}

    public function verbDictionary(dictObject)
    {}

    public function useVerbDictionary(dictObject)
    {}


    public function data()
    {
        if (empty($this->id))
        {
            $this->generateID();
        }
        // accessor function to retrieve the statement

        return array();
    }
}

?>
