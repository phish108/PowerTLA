<?php

/**
 * Base Class for LRSManager handlers managed by the system provider.
 */
abstract class LRSBase extends Logger
{
    const VERB_VOIDED = "http://adlnet.gov/expapi/verbs/voided";

    /**
     * Abstract Methods
     *
     * inherited classes should implement ONLY the abstract classes.
     */

    /**
     * get the internal user id for an actor object
     */
    abstract protected function getActorUserID($actor);

    /**
     * returns the statement for the given UUID
     */
    abstract protected function findStatementByUUID($uuid);

    /**
     * returns a document for the given document UUID.
     */
    abstract protected function findDocumentByUUID($uuid);

    /**
     * adds a statement into the activity stream.
     */
    abstract protected function addStatement($aLRSStatement);

    /**
     * updates a statement in the activity stream. (non standard)
     */
    abstract protected function updateStatement($aLRSStatement, $aOptions);

    /**
     * void a statement in the activity stream using the second statement
     *
     * @param {String} $uuid - the statement to void
     * @param {String} $vuuid - the voiding statement
     */
    abstract protected function voidStatement($targetUUID, $voidingUUID);

    /**
     * deletes a statement from the activity stream. (non standard)
     */
    abstract protected function deleteStatement($targetUUID);

    /**
     * reads the activity stream for the provided options
     *
     * valid options are day, month, year partitions, actor_id, verb_id or
     * object_id, score, duration, or registration.
     *
     * readActivityStream returns processed JSON statements (as Type Object)
     */
    abstract protected function readActivityStream($aOptions);

    /**
     * reads the activity stream for the provided options
     *
     * Instead of returning anything this function will call the callback for
     * each statement.
     *
     * Note that you have to use anonymous functions from within the service.
     *
     * readActivityStreamWithCallback provides unprocessed JSON statements
     * (as Type String).
     */
    abstract protected function readActivityStreamWithCallback($callback, $aOptions);

    /**
     * adds a new document with the provided options
     */
    abstract protected function addDocument($aDocument, $aOptions);

    /**
     * reads the documents for the given options.
     */
    abstract protected function readDocument($aOptions);

    /**
     * updates an existing document for the given options.
     */
    abstract protected function updateDocument($aDocument, $aOptions);

    /**
     * deletes all documents for the given options.
     */
    abstract protected function deleteDocument($aOptions);

    /**
     * Helper Functions
     */
    /**
     * quote a string in single quotes and escapes single quotes within.
     *
     * This function is needed because php does not support ? for arrays.
     *
     */
    protected function quote($str)
    {
        return "'". preg_replace("/(\')/", '${1}${1}', $str) . "'";
    }

    /**
     * builds a where statement from the given options. This method
     * ensures that only those fields are handled that are also part of
     * the pwrtla data schema.
     */
    protected function buildWhere($aOptions)
    {
        // out internal column map
        $types = array(
            "uuid"          => "text",
            "statement_id"  => "text",
            "document"      => "text",
            "doctype"       => "text",
            "statement"     => "text",
            "object_id"     => "text",
            "agent"         => "text",
            "agent_id"      => "text",
            "registration"  => "text",
            "user_id"       => "integer",
            "stored"        => "integer",
            "duration"      => "integer",
            "score"         => "integer",
            "verb_id"       => "text",
            "tsyear"        => "integer",
            "tsmonth"       => "integer",
            "tsday"         => "integer",
            "tshour"        => "integer",
            "tsminute"      => "integer"
        );

        $aWhere = array();

        foreach ($aOptions as $k => $v)
        {
            switch ($types[k])
            {
                case "text":
                    if (getType($v) == "array")
                    {
                        $aTmp = array();
                        foreach ($v as $t)
                        {
                            $aTmp[] = $this->quote($t);
                        }
                        $aWhere[] = $k . " IN " . implode(", ", $aTmp);
                    }
                    else
                    {
                        $aWhere[] = $k . " = " . $this->quote($v);
                    }
                    break;
                case "integer":
                    if (getType($v) == "array")
                    {
                        if (count($v) == 1)
                        {
                            $aWhere[] = $k . " > " . intval($v[0]);
                        }
                        if ($v[0] > $v[1]) {
                            $aWhere[] = $k . " < " . intval($v[0]);
                            $aWhere[] = $k . " > " . intval($v[1]);
                        }
                        else {
                            $aWhere[] = $k . " > " . intval($v[0]);
                            $aWhere[] = $k . " < " . intval($v[1]);
                        }
                    }
                    else
                    {
                        $aWhere[] = $k . " = " . intval($v);
                    }
                    break;
                default:
                    break;
            }
        }
        return implode(" AND ", $aWhere);
    }


    private function generateID()
    {
        $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0x0fff ) | 0x4000,
                        mt_rand( 0, 0x3fff ) | 0x8000,
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ));

        $this->statement["id"] = trim($uuid);
        return $this->statement["id"];
    }


    private function generateTimestamp()
    {
        $dt = new DateTime('NOW');
        return $dt->format(DateTime::ISO8601);
    }

    /**
     * Helper for updating statements
     */
    private function compareKVPairs($new, $old)
    {
        foreach ($new as $key => $value)
        {
            if (!array_key_exists($key, $old))
            {
                $old[$key] = $value;
            }
            else if (gettype($value) != gettype($old[$key]))
            {
                // type mismatch
                return false;
            }
            else
            {
                switch (gettype($value))
                {
                    case "array":
                    case "object":
                        if (!$this->compareKVPairs($value, $old[$key]))
                        {
                            return false;
                        }
                        break;
                    default:
                        if ($value != $old[$key])
                        {
                            return false;
                        }
                        break;
                }
            }
        }
        return true;
    }

    /**
     * translates the strange XAPI parameters into the internal values
     */
    private function translateGetOptions($getOpts)
    {
        $lrsOpts = array();

        foreach ($getOpts as $opt => $value)
        {
            switch ($opt)
            {
                case "since":
                case "until":
                    if (!array_key_exists("stored", $lrsOpts))
                    {
                        $lrsOpts["stored"] = array();
                    }
                    $lrsOpts["stored"][] = array($value);
                    break;
                case "agent":
                    $jV = json_decode($value, true);
                    $id;
                    if (array_key_exists("mbox", $jV))
                    {
                        $id = $jV["mbox"];
                    }
                    if (array_key_exists("openid", $jV))
                    {
                        $id = $jV["openid"];
                    }
                    if (array_key_exists("account", $jV) &&
                        array_key_exists("homepage", $jV["account"]))
                    {
                        $id = $jV["account"]["homepage"];
                    }

                    if (isset($id) && !empty($id))
                    {
                        $lrsOpts["actor_id"] = $id;
                    }
                    break;
                case "verb":
                    $lrsOpts["verb_id"] = $value;
                    break;
                case "object":
                case "activityId":
                    $lrsOpts["object_id"] = $value;
                    break;
                case "statementId":
                    $lrsOpts["statement_id"] = $value;
                    break;
                case "actor":
                    $lrsOpts["actor_id"] = $value;
                    break;
                case "score":
                    $lrsOpts["score"] = $value;
                    break;
                case "duration":
                    $lrsOpts["duration"] = array($value);
                    break;
                case "stateId":
                    $lrsOpts["doctype"] = "state";
                    $lrsOpts["uuid"] = $value;
                    break;
                case "profileId":
                    $lrsOpts["actor_id"] = $value;
                    if (array_key_exists("activityId", $getOpts))
                    {
                        $lrsOpts["doctype"] = "activity";
                        $lrsOpts["uuid"] =md5($getOpts["activityId"] . "-". $value);
                    }
                    else {
                        $lrsOpts["doctype"] = "profile";
                        $lrsOpts["uuid"]    = $value;
                    }
                    break;
                case "doctype":
                    // this is a helper, for the doc API
                    $lrsOpts["doctype"] = $value;
                    break;
                default:
                    break;
            }
        }

        return $lrsOpts;
    }

    /**
     * Service Interface
     */

    /**
     * PUT NEW single Statement (void or create)
     */
    public function handleStatement($statement)
    {
        if (isset($statement) &&
            gettype($statement) == "array")
        {
            $uuid = $statement["id"];
            if (!isset($uuid))
            {
                $uuid = $this->generateUUID();
                $statement["id"] = $uuid;
            }

            $actorid = null;
            if (isset($statement["actor"]))
            {
                $actorid = $statement["actor"]["mbox"];
                if (!isset($actorid) || emtpy($actorid))
                {
                    $actorid = $statement["actor"]["openid"];
                    if (!isset($actorid) || emtpy($actorid))
                    {
                        $actorid = $statement["actor"]["account"]["homepage"];
                    }
                }
            }

            if (!$this->findStatementByUUID($uuid) &&
               !(empty($actorid) ||
                 empty($statement["verb"]) ||
                 empty($statement["object"]) ||
                 empty($statement["verb"]["id"]) ||
                 empty($statement["object"]["id"])))
            {
                // verify the user
                $userid = $this->getActorUserID($statement["actor"]);
                if ($userid > 0)
                {
                    $lrsStatement = array();
                    $ts = $this->generateTimestamp();
                    $statement["stored"] = ts;

                    if (!isset($statement["timestamp"]) ||
                        empty($statement["timestamp"]))
                    {
                        $statement["timestamp"] = $ts;
                    }

                    $aTS = preg_split('/[\w:-]+/ ', $ts);

                    // pop seconds
                    array_pop($aTS);

                    // Check if this is a voiding statement

                    $lrsStatement["statement"]    = json_encode($statement);
                    $lrsStatement["stored"]       = implode("", $aTS);
                    $lrsStatement["uuid"]         = $uuid;

                    $lrsStatement["year"]         = $aTS[0];
                    $lrsStatement["day"]          = $aTS[3];
                    $lrsStatement["minute"]       = $aTS[4];
                    $lrsStatement["month"]        = $aTS[0] . $aTS[1];
                    $lrsStatement["day"]          = $aTS[0] . $aTS[1] . $aTS[2];

                    $lrsStatement["agent_id"]     = $actorid;
                    $lrsStatement["user_id"]      = $userid;
                    $lrsStatement["verb_id"]      = $statement["verb"]["id"];
                    $lrsStatement["object_id"]    = $statement["object"]["id"];
                    if (array_key_exists("context", $statement) &&
                        array_key_exists("registration", $statement["context"]))
                    {
                        $lrsStatement["registration"] = $statement["context"]["registration"];
                    }
                    if (array_key_exists("result", $statement) &&
                        array_key_exists("score", $statement["result"]))
                    {
                        $lrsStatement["score"] = $statement["result"]["score"];
                    }
                    if (array_key_exists("result", $statement) &&
                        array_key_exists("duration", $statement["result"]))
                    {
                        $lrsStatement["duration"] = $statement["result"]["duration"];
                    }
                    $this->addStatement($lrsStatement);
                    if ($lrsStatement["verb_id"] == LRSBase::VERB_VOIDED)
                    {
                        $this->voidStatement($lrsStatement["object_id"], $uuid);
                    }
                }
            }
        }
    }



    /**
     * PUT statement stream
     */
    public function processStatementStream($aStream)
    {
        if (isset($aStream) && gettype($aStream) == "array")
        {
            foreach ($aStream as $st)
            {
                $this->handleStatement($st);
            }
        }
    }

    /**
     * GET Filters
     */
    public function getStream($getOptions)
    {
        $opts = $this->translateGetOptions($getOptions);
        return $this->readActivityStream($opts);
    }

    /**
     * GET Filters With Callback
     */
    public function getStreamWithCallback($callback, $getOptions)
    {
        $opts = $this->translateGetOptions($getOptions);
        $this->readActivityStreamWithCallback($callback, $opts);
    }

    /**
     * GET one Callback
     */
    public function getAction($uuid)
    {
        return $this->findStatementByUUID($uuid);
    }

    /**
     * POST single Statement
     *
     * Note that there is not POST streaming!
     */
    public function extendStatement($statement)
    {
        $uuid = $statement["uuid"];
        if (isset($uuid) && !empty($uuid))
        {
            $origStatement = $this->findStatementByUUID($uuid);
            if (isset($origStatement) &&
                $this->compareKVPairs($statement, $origStatement))
            {
                // only extend without touching the integrety of the object
                $ts = $this->generateTimestamp();
                $statement["stored"]    = ts;

                $aTS = preg_split('/[\w:-]+/ ', $ts);

                // pop seconds
                array_pop($aTS);

                $lrsStatement = array();

                $lrsStatement["statement"] = json_encode($origStatement);
                $lrsStatement["stored"] = implode("", $aTS);

                if (array_key_exists("context", $statement) &&
                        array_key_exists("registration", $statement["context"]))
                {
                    $lrsStatement["registration"] = $statement["context"]["registration"];
                }
                if (array_key_exists("result", $statement) &&
                    array_key_exists("score", $statement["result"]))
                {
                    $lrsStatement["score"] = $statement["result"]["score"];
                }
                if (array_key_exists("result", $statement) &&
                    array_key_exists("duration", $statement["result"]))
                {
                    $lrsStatement["duration"] = $statement["result"]["duration"];
                }

                $this->updateStatement($lrsStatement,
                                       array("uuid" => $uuid));
                return TRUE;
            }
        }
        return FALSE;
    }


    public function getDocumentList($getOptions)
    {
        $opts = $this->translateGetOptions($getOptions);
        return $this->readDocument($opts);
    }

    public function getDocumentListWithCallback($callback, $getOptions)
    {
        $opts = $this->translateGetOptions($getOptions);
    }

    public function getDocument($uuid)
    {
        return $this->findDocumentByUUID($uuid);
    }

    public function storeDocument($doc, $getOptions)
    {
        if (!isset($getOptions))
        {
            $opts = $this->translateGetOptions($getOptions);
            $oDoc = $this->readDocument($opts);
            if (isset($oDoc) &&
                count($oDoc) == 1)
            {
                $this->updateDocument($doc, $opts);
            }
        }
    }

    public function createDocument($doc, $getOptions)
    {
        if (!isset($getOptions))
        {
            $opts = $this->translateGetOptions($getOptions);
            $oDoc = $this->readDocument($opts);
            if (!isset($oDoc) || count($oDoc) == 0)
            {
                $uuid = $this->db->generateID();
                $opts["uuid"] = $uuid;
                $this->addDocument($doc, $opts);
            }
        }
    }

    public function removeDocument($getOptions)
    {
        if (!isset($getOptions))
        {
            $opts = $this->translateGetOptions($getOptions);
            $oDoc = $this->readDocument($opts);
            if (isset($oDoc) && count($oDoc))
            {
                $this->deleteDocument($opts);
            }
        }
    }
}
?>