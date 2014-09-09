<?php

require_once 'Modules/Course/classes/class.ilCourseParticipant.php';
include_once 'Services/Membership/classes/class.ilParticipants.php';

class MCFilter extends Logger
{
    protected $vle;
    protected $dbh;
    protected $param;
    protected $values;
    protected $query;
    private   $types;
    private   $error;
    private   $dateLimit;
    private   $withTutor;
    private   $scope; // the scope selectors
    private   $curScope; // the scope selector mapping

    public function __construct($vle)
    {
        $this->vle     = $vle;
        $this->dbh     = $vle->getDBHandler();
        $this->param   = array();
        $this->query   = array();
        $this->values  = array();
        $this->types   = array();
        $this->scope = array();
        $this->curScope = array();
        $this->error  = "";
        $this->dateLimit = array();
        $this->withTutor = FALSE;
    }

    /*
     * {
     *    "id": "filterURI",     // the official reference to the filter, should provice a description
     *    "scope": ["selector"]  // additional path info data will be assigned to the provided call scope variables
     *    "query": [             // arrays refer to OR statements
     *      {                    // objects refer to AND statements
     *         "context.statement.id": { // dot notation for filter parameter
     *             "param": "keyname",   // param: keyname pair indicates required GET parameters; multiple for complex selects
     *             "map": {              // for param clauses "map" indicates how the param should be used.
     *                "query": {
     *                   "verb.id": { "value": "http://ilias.org/vocab/course/participation"},
     *                   "result.success": {"!value": true}, // leading ! means NOT
     *                   "object.id": {"map": "http://foo.bar.com/xyz/{param}"} // '{param}' indicates where the param should be mapped
     *                }
     *             }
     *         },
     *         "result.score.raw": {"value": 1},    // explicit value
     *         "agent.id": {"value": ["mailto:a@b.com", "mailto:b@b.com"]}, // several values possible
     *         "agent.id": {"param": "keyname", "map": "mailto:{param}"}    // simple parameter mapping (if no subqueries are needed)
     *      }
     *    ],
     * }
     *
     *
     * the limited mobler cards queries accept only the folowing queries
     * {
     *    query: {
     *       'context.statement.id': {param: cid} // course id
     *       'agent.id': {param: aid} // student id
     *       'result.score.raw': {param: sc} // score
     *       'result.duration: {param: dt} // duration
     *       'object.id': {param: oid} // question id
     *    }
     * }
     */
    public function addSelector($selector)
    {
        $params = array();
        $values = array();
        $query  = array();

        if (!empty($selector))
        {
            foreach ($selector["query"] as $sel => $qv)
            {
                switch ($sel)
                {
                    case 'context.statement.id':
                        if (array_key_exists("param", $qv))
                        {
                            $params["course_id"] = $qv["param"];
                        }
                        else if (array_key_exists("value", $qv))
                        {
                            $values[] = $qv["value"];
                            array_push($query, "course_id = ?");
                            array_push($this->types, "text");
                        }
                        break;
                    case 'agent.id':
                        if (array_key_exists("param", $qv))
                        {
                            $params["user_id"] = $qv["param"];
                        }
                        else if (array_key_exists("value", $qv))
                        {
                            $values[] = $qv["value"];
                            array_push($query, "user_id = ?");
                            array_push($this->types, "text");
                        }
                        break;
                    case 'result.score.raw':
                        if (array_key_exists("param", $qv))
                        {
                            $params["score"] = $qv["param"];
                        }
                        else if (array_key_exists("value", $qv))
                        {
                            $values[] = $qv["value"];
                            array_push($query, "score = ?");
                            array_push($this->types, "float");
                        }
                        break;
                    case 'result.duration':
                        if (array_key_exists("param", $qv))
                        {
                            $params["duration"] = $qv["param"];
                        }
                        else if (array_key_exists("value", $qv))
                        {
                            $values[] = $qv["value"];
                            array_push($query, "duration = ?");
                            array_push($this->types, "integer");
                        }
                        break;
                    case 'object.id':
                        if (array_key_exists("param", $qv))
                        {
                            $params["question_id"] = $qv["param"];
                        }
                        else if (array_key_exists("value", $qv))
                        {
                            $values[] = $qv["value"];
                            array_push($query, "question_id = ?");
                            array_push($this->types, "text");
                        }
                        break;
                    default:
                        break;
                }
            }
            $this->param  = $params;
            $this->values = $values;
            $this->query  = $query;

            if (isset($selector) &&
                isset($selector["scope"]) &&
                !empty($selector["scope"]))
            {
                foreach ($selector["scope"] as $c)
                {
                    $this->scope[] = $c;
                }
            }
        }
    }

    public function setScope($oCtxt)
    {
        if (isset($this->scope) && count($this->scope))
        {
            foreach ($this->scope as $scope)
            {
                $this->curScope[$scope] = array_shift($oCtxt);
            }
        }
    }

    public function setParams($oParam)
    {
        $this->error = "";
        // implicit date parameter, this cannot get used by the filter
        if (array_key_exists("date", $oParam))
        {
            $this->dateLimit = $oParam["date"];
            unset($oParam["date"]);
        }

        if (array_key_exists("include", $oParam) && in_array("instructor", $oParam["include"]))
        {
            $this->withTutor = TRUE;
            unset($oParam["include"]);
        }

        foreach ($this->param as $k => $v)
        {
            if (array_key_exists($v, $oParam))
            {
                $type = "text";
                if ($k == "duration")
                {
                    $type = "integer";
                }
                elseif ($k == "score")
                {
                    $type = "float";
                }

                if (is_array($oParam[$v]))
                {
                    if (count($oParam[$v]) > 1)
                    {
                        $qstr = $k . ' IN (';
                        $qarr = array();

                        foreach ($oParam[$v] as $i)
                        {
                            array_push($qarr, "?");
                            array_push($this->values, $i);
                            array_push($this->types, $type);
                        }

                        $qstr .= implode(",", $qarr);
                        $qstr .= ')';
                        array_push($this->query, $qstr);
                    }
                    elseif (!empty($oParam[$v]))
                    {
                        array_push($this->values, $oParam[$v][0]);
                        array_push($this->query, $k . " = ?");
                        array_push($this->types, $type);
                    }
                    else
                    {
                        $this->error = "missing param";
                    }
                }
            }
            else {
                $this->error = "missing param";
            }
        }
    }

    public function apply()
    {
        // fetch the object reference information in one go, because
        // ilias hardly ever uses the actual object id.
        // $sql = "SELECT s.*, r.ref_id FROM ui_uihk_xmob_stat s, object_reference r WHERE s.course_id = r.obj_id"; // new
        $sql = "SELECT s.*, r.ref_id FROM isnlc_statistics s, object_reference r WHERE s.course_id = r.obj_id"; // old

        $query = array();

        if (array_key_exists("context.statement.id", $this->curScope) && isset($this->curScope["context.statement.id"]))
        {
            // check whether the current user is a member of the curent course context
            $ctxtCourseID;
            $refsql = "SELECT ref_id FROM object_reference WHERE obj_id = ?";
            $sth    = $this->dbh->db->prepare($refsql, array("integer"));
            if (PEAR::isError($sth))
            {
                return array();
            }
            $res    = $sth->execute(array($this->curScope["context.statement.id"]));
            if ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
            {
                $ctxtCourseID = $row["ref_id"];
            }
            $sth->free();

            // check if the active user is a member in the scope
            if (!($this->vle->getActiveUserId() &&
                  ilParticipants::_isParticipant($ctxtCourseID,
                                                 $this->vle->getActiveUserId())))
            {
                // check whether the ative user is priviledged to access the requested scope.
                // At this level the filter MUST apply sub scopes according to the user's
                // privileges on the context object within the VLE.

                // if the user is an admin or a special service they may continue
                $this->log("user is not a participant");
//                    $this->error = "forbidden"; // TODO pass directly to RESTling
//                    return array();
            }

            $query[] = "s.course_id = ?";
            $this->types[] = "integer";
            $this->values[] = $this->curScope["context.statement.id"];

//            $ctxtuser = new ilCourseParticipant($ctxtCourseID, $this->vle->getActiveUserId());

//            if (!($ctxtuser->isAdmin() || $ctxtuser->isTutor))
//            {
//                // for now normal students can see only their personal data
//                $query[] = "s.user_id = ?";
//                $this->types[] = "integer";
//                $this->values[] = $this->vle->getActiveUserId();
//            }
        }

        if (count($this->dateLimit))
        {
            sort($this->dateLimit);

            $min = array_shift($this->dateLimit);
            $max = array_pop($this->dateLimit);

            if ($max > 0)
            {
                array_push($query, "day > ?");
                array_push($query, "day < ?");
                array_push($this->values, $min);
                array_push($this->values, $max);
            }
            else
            {
                array_push($query, "day = ?");
                array_push($this->values, $min);
            }
        }

        if (count($this->query))
        {
            $query = array_merge($this->query, $query);
        }

        if (count($query))
        {
            $sql .= " AND " . implode(" AND ", $query);
        }

        $this->log("MC FILTER SQL " . $sql);
        // $sth = $this->dbh->prepare($sql);
        // $res = $sth->execute($this->values);

        if (strlen($this->error))
        {
            $this->log("stop loading : " . $this->error);
            return;
        }

        $rv = array();

        $userDict = array();
        $objDict  = array("stackhandler" => array("id" => "http://mobinaut.io/badges/stackhandler",
                                                   "display"=> array("en" => "Stack Handler")),
                           "cardburner"   => array("id" => "http://mobinaut.io/badges/cardburner",
                                                   "display"=> array("en" => "Card Burner")));
        $ctxtDict = array();

        $verbDict = array("qti.item.response" => array("id" => "http://imsglobal.com/vocab/qti/response/item",
                                                       "display" => array("en" => "Responded to a test item",
                                                                          "de" => "Testfrage beantwortet")),
                          "mozilla.achieve.badge" => array("id" => "http://openbadges.org/vocab/earned/badge",
                                                           "display" => array("en" => "Earned badge",
                                                                              "de" => "Belohnung verdient")),
                          "participate"           => array("id" => "http://ilias.org/vocab/course/participation",
                                                           "display" => array("en" => "Course participation",
                                                                              "de" => "am Kurs teilgenommen")),
                         );

        $resDict = array("0"   => array("score" => array("raw" => "0", "scaled" => -1, "success" => FALSE, "completion" => FALSE)),
                         "0.5" => array("score" => array("raw" => "0.5", "scaled" => 0, "success" => FALSE, "completion" => FALSE)),
                         "1"   => array("score" => array("raw" => "1", "scaled" => 1, "success" => TRUE, "completion" => FALSE)));

        $sth = $this->dbh->db->prepare($sql, $this->types);

        //$this->log(implode(", ", $this->types));
        //$this->log(implode(", ", $this->values));
        if (!PEAR::isError($sth))
        {
            $res = $sth->execute($this->values);
        }
        else
        {
            $this->log("DB ERROR " . $sth->getMessage());
        }

        while ($record = $res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            // $this->log(json_encode($record))
            $cuser = new ilCourseParticipant($record["ref_id"], $record["user_id"]);

            // populate context dict
            if (!array_key_exists ($record["user_id"] . $record["course_id"], $ctxtDict))
            {
                if($cuser->isAdmin() || $cuser->isTutor())
                {
                    $pseudoStatement = "course.admin-" . $record["course_id"] . "-" . $record["user_id"];
                    $ctxtDict[$record["user_id"] . $record["course_id"]] = array("statement" => array("objectType" => "StatementRef",
                                                                                         "id" => $pseudoStatement));
                }
                else
                {
                    $pseudoStatement = "course.participate-" . $record["course_id"] . "-" . $record["user_id"];
                    $ctxtDict[$record["user_id"] . $record["course_id"]] = array("statement" => array("objectType" => "StatementRef",
                                                                                 "id" => $pseudoStatement));
                }
            }

            $s = new XAPIStatement();
            $s->addID($record["id"]);

            if ($record["duration"] > 0)
            {
                $s->addVerb($verbDict["qti.item.response"]);
                $result = $resDict[$record["score"]];
                $result["duration"] = $record["duration"];
                $s->addResult($result);
            }
            else
            {
                $s->addVerb($verbDict["mozilla.achieve.badge"]);
            }
            $dt = new DateTime();
            $dt->setTimestamp(intval(intval($record["day"])/1000));
            // populate user dict
            if (!array_key_exists($record["user_id"], $userDict))
            {
                // need to fetch agent information
                // FIXME - avoid VLE specific code
                $oUser    = new ilObjUser($record["user_id"]);
                $fullName = $oUser->getFirstname() . " " . $oUser->getLastname();
                // exclude course administrator data from the steam
                // we need the officila ref_id not the internal course id

                if(!$this->withTutor && ($cuser->isAdmin() || $cuser->isTutor()))
                {
                    $this->log('remove course admins');
                    continue;
                }
                $userDict[$record["user_id"]] = array("id" => "mailto:" . $oUser->getEmail(),
                                                      "name" => $fullName);

                $ts = new XAPIStatement();

                $ts->addID($ctxtDict[$record["user_id"] . $record["course_id"]]["statement"]["id"]);
                $ts->addAgent($userDict[$record["user_id"]]);
                $ts->addVerb($verbDict["participate"]);
                $ts->addObject(array("id"=> $this->vle->getBaseURL() . "tla/restservice/content/course.php/" . $record["course_id"]));

                $ts->addTimestamp($dt->format(DateTime::ISO8601));
                array_push($rv, $ts->result());

            }
            $s->addTimestamp($dt->format(DateTime::ISO8601));
            $s->addAgent($userDict[$record["user_id"]]);

            // polulate object dict
            if (!array_key_exists($record["question_id"], $objDict))
            {
                $result = $this->dbh->queryF("SELECT qpl_questions.*, qpl_qst_type.* FROM qpl_questions, qpl_qst_type WHERE qpl_questions.original_id IS NULL AND qpl_questions.question_id = %s AND qpl_questions.tstamp > 0 AND qpl_questions.question_type_fi = qpl_qst_type.question_type_id",
                        array('integer'),
                        array($record["question_id"])
                );

                $data = $this->dbh->fetchAssoc($result);
                $urlid = $this->vle->getBaseURL() . "tla/restservice/content/qti.php/pool/" . $data["obj_fi"] . "/" . $data["question_id"];
                $question = $data["question_text"];
                if ($data["type_tag"] == "assClozeTest")
                {
                    $question = $data["title"];
                }

                $objDict[$record["question_id"]] = array("id" => $urlid,
                                                         "definition" => array("name" => array("C" => $question),
                                                                               "type" => "http://imsglobal.com/vocab/qti/item/" . $data["type_tag"]));

            }

            $s->addObject($objDict[$record["question_id"]]);

            $s->addContext($ctxtDict[$record["user_id"] . $record["course_id"]]);
            // $this->log(json_encode($s->result()));
            array_push($rv, $s->result());
        }

        $sth->free();

        return $rv;
    }

    public function match($statement)
    {
        return false;
    }

    public function lastError()
    {
        return $this->error;
    }

    public function getParams()
    {
        return $this->param;
    }
}
?>
