<?php

/**
 * @class XAPIStatement
 */

class XAPIStatement extends Logger
{
    protected $statement;
    protected $activityId;
    protected $stored;
    protected $since;
    protected $userid;

    public function __construct()
    {
        $this->statement = array();
    }

    /** *************************************
     * SUBCLASSES MUST IMPLEMENT THESE FUNCTIONS
     */

    // find activity loads the activity into the statement if found
    // this method must take $this->since into account
    protected function findActivity()
    {
        return false;
    }

    // Overload this method for writing data into the activity stream
    protected function storeStatement()
    {}

    // MUST RETURN TRUE IF THE NEW ID IS NOT IN THE DATABASE
    // IF THE NEW ID IS ALREADY IN THE DATABASE, MEANS THAT THE RECORD
    // HAS BEEN SENT ALREADY
    public function validateNewId()
    {
        return true;
    }

    // must check if the user exists
    protected function validateAgent()
    {
        return true;
    }

    protected function read()
    {}

     /** *************************************
     * SUBCLASSES Dont need to IMPLEMENT THESE FUNCTIONS
     */
    public function isStream()
    {
        if (isset($this->statement) &&
            gettype($this->statement) == "array" &&
            count($this->statement))
        {
            return true;
        }
        return false;
    }

    public function add($newstatement)
    {
        if (isset($newstatement) && !empty($newstatement))
        {
            if (gettype($newstatement) == "array" &&
                count($newstatement) <= 1)
            {
                if (count($newstatement) == 1)
                {
                    $this->statement = $newstatement[0];
                }
            }
            else
            {
                $this->statement = $newstatement;
            }
        }
    }

    public function setSince($sinceTime)
    {
        $since = new DataTime($sinceTime);
        $since = $since->getTimestamp();
        if ($since > 0) {
            $this->since = $since;
        }
    }

    public function setActivityId($activityId)
    {
        if (isset($activityId) && !empty($activityId))
        {
            $this->activityId = $activityId;
            if (!$this->findActivity())
            {
                $this->activityId = null;
            }
        }
    }

    public function getAgent()
    {
        if (isset($this->statement) &&
            !empty($this->statement) &&
            isset($this->statement["actor"]) &&
            !empty($this->statement["actor"]))
        {
            return $this->statement["actor"];
        }
    }

    public function generateID()
    {

        $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0x0fff ) | 0x4000,
                        mt_rand( 0, 0x3fff ) | 0x8000,
                        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ));

        $this->statement["id"] = trim($uuid);
        return $this->statement["id"];
    }

    public function makeTimestamp() ///< the time is now
    {
        $dt = new DateTime('NOW');
        $this->statement["timestamp"] = $dt->format(DateTime::ISO8601);
    }

    public function makeStoredTimestamp()
    {
        $dt = new DateTime('NOW');
        $this->statement["stored"] = $dt->format(DateTime::ISO8601);
        $this->stored = $dt->getTimestamp();
    }

    public function validate()
    {
        if (gettype($this->statement) == "array")
        {
            $aStatements = $this->statement;
            $newStatement = array();
            foreach ($aStatements as $value)
            {
                $this->statement = $value;
                // keep only the valid statements
                if ($this->validateStatement() &&
                    $this->validateAgent())
                {
                    array_push($newStatement, $value);
                }
            }
            if (!count($newStatement))
            {
                return false;
            }
            $this->statement = $newStatement;

        }
        else if (gettype($this->statement) == "object")
        {
            return ($this->validateStatement() && $this->validateAgent());
        }
        return true;
    }

    protected function validateStatement()
    {
        // this function returns the statement only if it is valid
        if (!(empty($this->statement["actor"]) ||
              empty($this->statement["verb"]) ||
              empty($this->statement["object"]) ||
              empty($this->statement["verb"]["id"]) ||
              empty($this->statement["object"]["id"])))
        {
            // FIXME: need to validate if verb.id and object.id are IRIs

            if (!array_key_exists("id", $this->statement) ||
                empty($this->statement["id"]))
            {
                $this->generateID();
            }

            if (!array_key_exists("timestamp", $this->statement) ||
                empty($this->statement["timestamp"]))
            {
                $this->makeTimestamp();
            }

            return true;
        }

        return false;
    }

    public function update()
    {
        // ensure that only new fields enter the record
        $statement = $this->statement;
        if (!array_key_exists("id", $statement))
        {
            return false;
        }

        $this->activityId = $statement["id"];

        if (!$this->findActivity())
        {
            return false;
        }

        return $this->compareKVPairs($new, $old);
    }

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

    // TODO VERIFY INVALIDATE
    public function invalidate()
    {}

    public function store()
    {
        if ($this->isStream())
        {
            $aStatements = $this->statement;
            foreach ($aStatements as $s)
            {
                $this->statement = $s;
                $this->makeStoredTimestamp();
                $this->storeStatement();
            }
            return true;
        }

        $this->makeStoredTimestamp();
        $this->storeStatement();
        return true;
    }

    public function create()
    {
        if ($this->isStream())
        {
            $aStatements = $this->statement;
            foreach ($aStatements as $s)
            {
                if ($this->validateNewId())
                {
                    $this->statement = $s;
                    $this->makeStoredTimestamp();
                    $this->storeStatement();
                }
            }
            return true;
        }
        else if ($this->validateNewID())
        {
            $this->makeStoredTimestamp();
            $this->storeStatement();
            return true;
        }
        return false;
    }

    public function get()
    {
        return $this->statement;
    }
}

?>
