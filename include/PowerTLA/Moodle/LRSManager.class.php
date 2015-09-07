<?php
/**
 * The LRS Manager Class implements the DB Interface for the VLE
 *
 * LRS Base provides all validatation functions
 */
class LRSManager extends LRSBase
{
    private $db;

    public function __construct()
    {
        global $DB;
        $this->db = $DB;
    }

    /**
     * returns the actor's internal user id
     */
    protected function getActorUserID($actor)
    {
        if (array_key_exists("mbox", $actor))
        {
            $email = array_pop(explode(":", $actor["mbox"]));
            if (isset($email) &&
                !empty($email))
            {
                // get user id from the user table
                if ($user = $this->db->get_record('user',
                                                  array("mail" => $email)))
                {
                    return $user->id;
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

        if (isset($itoken) &&
            !empty($itoken))
        {
            if ($utoken = $this->db->get_record("pwrtla_usertokens",
                                             array("user_token" => $itoken)))
            {
                return $utoken->user_id;
            }
        }

        return -1;
    }

    protected function findStatementByUUID($uuid)
    {
        $statement = null;
        if(isset($uuid) && !empty($uuid))
        {
            if ($rec = $this->db->get_record("pwrtla_xapistatements",
                                         array("uuid" => $uuid)))
            {
                $statement = json_decode($rec->statement);
            }
        }
        return $statement;
    }
    protected function findDocumentByUUID($uuid)
    {
        $document = null;
        if(isset($uuid) && !empty($uuid))
        {
            if ($rec = $this->db->get_record("pwrtla_xapidocuments",
                                         array("uuid" => $uuid)))
            {
                $document = json_decode($rec->document);
            }
        }
        return $document;
    }

    protected function readActivityStreamWithCallback($callback, $aOptions) {}

    protected function readActivityStream($aOptions = [])
    {
        $aStream = array();
        $where = $this->buildWhere($aOptions);
        if (isset($where) && !empty($where))
        {
            $records = $this->db->get_records_select("pwrtla_xapistatements",
                                                     $where);
            foreach ($records as $s)
            {
                $aStream[] = json_decode($s->statement);
            }
        }
        return $aStream;
    }

    protected function readDocument($aOptions = [])
    {
        $aStream = array();
        $where = $this->buildWhere($aOptions);
        if (isset($where) && !empty($where))
        {
            $records = $this->db->get_records_select("pwrtla_xapidocuments",
                                                     $where);
            foreach ($records as $s)
            {
                $aStream[] = json_decode($s->document);
            }
        }
        return $aStream;
    }

    protected function readActivtyStreamWithCallback($cb, $aOptions = [])
    {
        $where = $this->buildWhere($aOptions);
        if (isset($where) && !empty($where))
        {
            $records = $this->db->get_records_select("pwrtla_xapistatements",
                                                     $where);
            foreach ($records as $s)
            {
                call_user_func($cb, $s->statement);
            }
        }
    }

    protected function readDocumentWithCallback($cb, $aOptions = [])
    {
        $where = $this->buildWhere($aOptions);
        if (isset($where) && !empty($where))
        {
            $records = $this->db->get_records_select("pwrtla_xapidocuments",
                                                     $where);
            foreach ($records as $s)
            {
                call_user_func($cb, $s->document);
            }
        }
    }

    protected function addStatement($aLRSStatement)
    {
        $this->db->insert_record("pwrtla_xapistatements",
                                 $aLRSStatement);
    }

    protected function updateStatement($aLRSStatement, $aOptions)
    {
        $where = $this->buildWhere($aOptions);
        if (isset($aStatement) &&
            !isset($where) &&
            !empty($where))
        {
            $strDoc = json_encode($aStatement);
            $sql = "UPDATE TABLE pwrtla_xapidocument SET document = ? WHERE " . $where;
            $this->db->execute($sql, array($strDoc));
        }
    }
    protected function deleteStatement($aOptions)
    {
        if($where = $this->buildWhere($aOptions))
        {
            $this->db->delete_records_select("pwrtla_xapistatements",
                                             $where);
        }
    }

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
        $this->db->insert_record("pwrtla_xapidocuments",
                                 array((object)$dbstatement));
    }

    protected function updateDocument($aDocument, $aOptions)
    {
        $where = $this->buildWhere($aOptions);
        if (isset($aDocument) &&
            !isset($where) &&
            !empty($where))
        {
            $strDoc = json_encode($aDocument);
            $sql = "UPDATE TABLE pwrtla_xapidocument SET document = ? WHERE " . $where;
            $this->db->execute($sql, array($strDoc));
        }
    }

    protected function deleteDocument($aOptions)
    {
        if($where = $this->buildWhere($aOptions))
        {
            $this->db->delete_records_select("pwrtla_xapidocuments",
                                             $where);
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
            $sql = "UPDATE TABLE pwrtla_xapistatements SET voided = ? WHERE uuid = ?";

            $this->db->execute($sql, array($vuuid, $uuid));
        }
    }
}
?>