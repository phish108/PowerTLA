<?php
/**
 * The LRS Manager Class implements the DB Interface for the VLE
 *
 * LRS Base provides all validatation functions functions
 */
class LRSManager extends LRSBase
{
    private $db;

    public function __construct()
    {
        global $ilDB;
        $this->db = $ilDB;
    }

    /**
     * returns the actor's internal user id
     */
    protected function getActorUserID($actor)
    {

        if (array_key_exists("mbox", $actor))
        {
            $email = array_pop(explode(":", $actor["mbox"]));
            if (isset($email) && !empty($email))
            {
                $r = $this->db->queryF("SELECT usr_id FROM usr_data ".
                                   "WHERE email = %s",
                                   array("text"),
                                   array($email));

                if ($data = $this->db->fetchAssoc($r))
                {
                    return $data["usr_id"];
                }
            }
        }

        if (array_key_exists("openid", $actor))
        {
            // note if the itoken is not there, then it is
            // not our user and we will reject.

            // TODO we MUST check if the URL belongs to us, too

            $itoken = array_pop(explode("/", $array["openid"]));
        }

        if (array_key_exists("account", $actor) &&
            array_key_exists("homepage", $actor["account"]) &&
            !empty($actor["account"]["homepage"]))
        {
            $itoken = array_pop(explode("/", $actor["account"]["homepage"]));
        }

        if (isset($itoken))
        {
            $r = $ilDB->queryF("SELECT user_id FROM pwrtla_usertokens ".
                               "WHERE user_token= %s",
                               array("text"),
                               array($itoken));

            if ($data = $this->db->fetchAssoc($r))
            {
                return $data["user_id"];
            }
        }

        return -1;
    }

    protected function findStatementByUUID($uuid)
    {
        if (isset($uuid) && !empty($uuid))
        {
            $r = $this->db->queryF("SELECT statement FROM pwrtla_xapistatements WHERE uuid = %s",
                                   array(self::$StatementTypes["uuid"]),
                                   array($uuid));

            if ($data = $this->db->fetchAssoc($r))
            {
                return json_decode($data["statement"]);
            }
        }
        return null;
    }

    protected function addStatement($aLRSStatement)
    {
        $dbstatement = array();
        foreach ($aLRSStatement as $col => $val)
        {
            if (array_key_exists($col, self::$StatementTypes))
            {
                $dbstatement[$col] = array(self::$StatementTypes[$col],
                                           $aLRSStatement[$col]);
            }
        }

        $this->db->insert("pwrtla_xapistatements", $dbstatement);
    }

    protected function updateStatement($aLRSStatement, $aOptions)
    {
        $where = $this->buildWhere($aOptions);
        if (isset($aLRSStatement) && isset($where) && !empty($where))
        {
            $dbtypes = array();
            $dbvals  = array();
            $dbset   = array();
            foreach ($aLRSStatement as $col => $val)
            {
                if (array_key_exists($col, self::$StatementTypes))
                {
                    $dbtypes[] = self::$StatementTypes[$col];
                    $dbvals[]  = $aLRSStatement[$col];
                    $dbset[]   = $col . " = %s";
                }
            }
            $sql = "UPDATE TABLE pwrtla_xapistatements SET " . implode(", ", $dbset);
            $sql.= " WHERE " . $where;

            $this->db->manipulateF($sql, $dbtypes, $dbvals);
        }
    }

    protected function voidStatement($uuid, $vuuid)
    {
        $statement = $this->findStatementByUUID($uuid);
        if (isset($vuuid) &&
            !empty($vuuid) &&
            isset($statement))
        {
            $uuid   = $this->quote($uuid);
            $vuuid  = $this->quote($vuuid);
            $sql = "UPDATE TABLE pwrtla_xapistatements SET voided = " . $vuuid . " WHERE uuid = " . $uuid;

            $this->db->manipulateF($sql);
        }
    }

    protected function deleteStatement($uuid)
    {
        $sql = "DELETE FROM pwrtla_xapistatements WHERE uuid = "
             . $this->quote($uuid);
        $this->db->manipulateF($sql);
    }

    protected function readActivityStream($aOptions = [])
    {
        $aStream = array();

        $where = $this->buildWhere($aOptions);
        $sql = "SELECT statement FROM pwrtla_xapistatements ";
        if (!empty($where))
        {
            $sql .= "WHERE voided is null AND " . $where;
        }

        $r = $this->db->queryF($sql);
        while ($data = $this->db->fetchAssoc($r))
        {
            // fixme: use a callback
            $aStream[] = json_decode($data["statement"]);
        }

        return $aStream;
    }

    protected function readActivityStreamWithCallback($cb, $aOptions = [])
    {
        $where = $this->buildWhere($aOptions);
        $sql = "SELECT statement FROM pwrtla_xapistatements ";
        if (!empty($where))
        {
            $sql .= "WHERE voided is null AND " . $where;
        }

        $r = $this->db->queryF($sql);
        while ($data = $this->db->fetchAssoc($r))
        {
            call_user_func($cb, $data["statement"]);
        }
    }

    /**
     * returns the parsed document if it exists for the given uuid.
     *
     * if the UUID does not exist, then the function returns null.
     *
     * Verify whether the a document uuid is in the database
     */
    protected function findDocumentByUUID($uuid)
    {
        if (isset($uuid) && !empty($uuid))
        {
            $r = $this->db->queryF("SELECT statement FROM pwrtla_xapidocuments WHERE uuid = %s",
                                   array(self::$DocumentTypes["uuid"]),
                                   array($uuid));

            if ($data = $this->db->fetchAssoc($r))
            {
                return json_decode($data["document"]);
            }
        }
        return null;
    }

    /**
     * adds a new document data set to the database.
     */
    protected function addDocument($aDocument, $aOptions)
    {
        $dbstatement = array(
            "document" => array("text", json_encode($aDocument))
        );

        foreach ($aOptions as $col => $val)
        {
            if (array_key_exists($col, self::$DocumentTypes))
            {
                $dbstatement[$col] = array(self::$DocumentTypes[$col],
                                           $aOptions[$col]);
            }
        }
        $this->db->insert("pwrtla_xapidocuments", $dbstatement);
    }

    protected function readDocument($aOptions)
    {
        $aDocs = null;
        $where = $this->buildWhere($aOptions);
        if (!empty($where))
        {
            $aDocs = array();
            $sql = "SELECT document FROM pwrtla_xapidocuments WHERE " . $where;
            $r = $this->db->queryF($sql);
            while ($data = $this->db->fetchAssoc($r))
            {
                // fixme: use a callback
                $aDocs[] = json_decode($data["document"]);
            }
        }
        return $aDocs;
    }

    protected function updateDocument($aDocument, $aOptions)
    {
        $dbDoc = $this->quote(json_encode($aDocument));
        $where = $this->buildWhere($aOptions);
        if (!isset($where) && !empty($where))
        {
            $sql = "UPDATE TABLE pwrtla_xapidocument SET document = " . $dbDoc . " WHERE ";
            $sql .= $where;
            $this->db->manipulateF($sql);
        }
    }

    protected function deleteDocument($aOptions)
    {
        $where = $this->buildWhere($aOptions);
        if (!isset($where) && !empty($where))
        {
            $sql = "DELETE FROM pwrtla_xapidocument WHERE " . $where;
            $this->db->manipulateF($sql);
        }
    }
}

?>
