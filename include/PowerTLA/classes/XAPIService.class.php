<?php

/**
 *
 */
class XAPIService extends RESTling
{
    /**
     * @property $mode
     */
    protected $mode;
    protected $feature;

    protected $filter_id;

    protected $input;

    protected function validateURI()
    {
        parent::validateURI();

        if($this->status === RESTling::OK)
        {
            $aPI = explode('/', $this->path_info);
            $this->mode    = array_shift($aPI);
            $this->feature = array_shift($aPI);

            // reset the mode and feature for Our filter API
            if (!empty($this->mode)
                && $this->mode === 'statements'
                && !empty($this->feature)
                && $this->feature === 'filter')
            {
                $this->mode = $this->feature;
                $this->feature = "result";
                $this->filter_id= array_shift($aPI);
            }
        }
    }

    protected function prepareOperation() {
        // This service is quite complex regarding the permitted methods.
        // the prepareOperation() method generates service functions directly from the
        // request.

        // The class provides
        $action_name = strtolower($this->method);

        // translate put and post to insert and update
        switch ($action_name) {
            case 'put':
                $action_name = 'insert';
                break;
            case 'post':
                $action_name = 'update';
                break;
            default:
                break;
        }

        $action_name .= '_' . strtolower($this->mode);

        if (!empty($this->feature))
        {
            $action_name .= "_" . strtolower($this->feature);
        }

        $this->operation = $action_name;
        $this->log("call method " . $action_name);
    }

    // About resource
    protected function get_about()
    {
        $this->data = array('version' => array('1.0.0'));
    }

    // Statement API

    /**
     * @method void store_single_statement()
     *
     * Helper function to store single XAPI statements into our database.
     * used of PUT and POST statements
     */
    private function store_single_statement($statement) {
        // insert the statement and index the context
        if($this->check_trigger($statement))
        {
            $this->call_trigger();
        }
    }

    protected function get_statements()
    {
        // get all statements
        $userDict = array("1234" => array("id" => "mailto:foo@example.org",
                                          "name" => "Foo Bar"),
                          "1235" => array("id" => "mailto:hello.world@example.org",
                                          "name" => "Hello World"));

        $verbDict = array("qti.response.item" => array("id" => "http://imsglobal.com/vocab/qti/response/item",
                                                       "display" => array("en" => "Responded to a test item",
                                                                          "de" => "Testfrage beantwortet")),
                          "ob.achieve.badge" => array("id" => "http://openbadges.org/vocab/earned/badge",
                                                      "display" => array("en" => "Earned badge",
                                                                         "de" => "Verdiente Belohnung")),
                          "course.participate.start" => array("id" => "http://ilias.org/vocab/course/participation/start",
                                                              "display" => array("en" => "Course participation started",
                                                                                 "de" => "Kursteilnahme begonnen")),
                          "course.participate.end" => array("id" => "http://ilias.org/vocab/course/participation/end",
                                                              "display" => array("en" => "Course participation ended",
                                                                                 "de" => "Kursteilnahme abgeschlossen")),
                         );

        $objectDict = array("123" => array("id" => "http://pfp.ethz.ch/qti/pool/54321/123",
                                           "definition" => array("name" => array("de" => "frage 1"),
                                                                 "type" => "http://imsglobal.com/vocab/qti/item")),
                            "4122" => array("id" => "http://pfp.ethz.ch/course/4122",
                                           "definition" => array("name" => array("de" => "UZH Test Kurs"),
                                                                 "type" => "http://ilias.org/vocab/course")),
                            "124" => array("id" => "http://pfp.ethz.ch/qti/pool/54321/124",
                                           "definition" => array("name" => array("de" => "frage 2"),
                                                                 "type" => "http://imsglobal.com/vocab/qti/item"))
                           );

        $resDict = array("0" => array("score" => array("raw" => "0", "scaled" => -1, "success" => FALSE, "completion" => FALSE)),
                         "0.5" => array("score" => array("raw" => "0.5", "scaled" => 0, "success" => FALSE, "completion" => FALSE)),
                         "1" => array("score" => array("raw" => "1", "scaled" => 1, "success" => TRUE, "completion" => FALSE)));

        $ctxtDict = array(
            "1234-course.enroll-4122"=> array("statement" => array("objectType" => "StatementRef", "id" => "1234-course.enroll-4122")),
            "1235-course.enroll-4122"=> array("statement" => array("objectType" => "StatementRef", "id" => "1235-course.enroll-4122"))
        );

        $jsonfeed   = array();

        $s = new XAPIStatement();
        $s->addID('1234-course.enroll-4122');
        $s->addAgent($userDict["1234"]);
        $s->addVerb($verbDict["course.participate.start"]);
        $s->addObject($objectDict["4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addID('1235-course.enroll-4122');
        $s->addAgent($userDict["1235"]);
        $s->addVerb($verbDict["course.participate.start"]);
        $s->addObject($objectDict["4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addAgent($userDict["1234"]);
        $s->addVerb($verbDict["qti.response.item"]);
        $s->addObject($objectDict["123"]);
        $s->addResult($resDict["0"]);
        $s->addContext($ctxtDict["1234-course.enroll-4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addAgent($userDict["1235"]);
        $s->addVerb($verbDict["qti.response.item"]);
        $s->addObject($objectDict["123"]);
        $s->addResult($resDict["0.5"]);
        $s->addContext($ctxtDict["1235-course.enroll-4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addAgent($userDict["1235"]);
        $s->addVerb($verbDict["qti.response.item"]);
        $s->addObject($objectDict["124"]);
        $s->addResult($resDict["0.5"]);
        $s->addContext($ctxtDict["1235-course.enroll-4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addAgent($userDict["1234"]);
        $s->addVerb($verbDict["qti.response.item"]);
        $s->addObject($objectDict["124"]);
        $s->addResult($resDict["1"]);
        $s->addContext($ctxtDict["1234-course.enroll-4122"]);
        array_push($jsonfeed,$s->result());

        $s = new XAPIStatement();
        $s->addAgent($userDict["1234"]);
        $s->addVerb($verbDict["qti.response.item"]);
        $s->addObject($objectDict["123"]);
        $s->addResult($resDict["1"]);
        $s->addContext($ctxtDict["1234-course.enroll-4122"]);
        array_push($jsonfeed,$s->result());

        $this->data = $jsonfeed;
    }

    protected function insert_statements()
    {
        // for each statement in the input
        $this->log('insert statement');
        $this->store_single_statement($this->input);
    }
    protected function update_statements()
    {
        // not sure if this is a correct mode

        // for each statement in the input
        // check whether the statement is stored and remove it if this the case.
        $this->log('update statement');
        $this->store_single_statement($this->input);
    }

    protected function delete_statements()
    {
        $this->missing();
    }

    // Agent Document API
    protected function get_agents()
    {
        $this->missing();
    }

    protected function get_agents_profile()
    {
        $this->missing();
    }
    protected function insert_agents_profile()
    {
        $this->missing();
    }
    protected function update_agents_profile()
    {
        $this->missing();
    }
    protected function delete_agents_profile()
    {
        $this->missing();
    }

    // Activities Document API
    protected function get_activities()
    {
        $this->missing();
    }

    protected function get_activities_profile()
    {
        $this->missing();
    }
    protected function insert_activities_profile()
    {
        $this->missing();
    }
    protected function update_activities_profile()
    {
        $this->missing();
    }
    protected function delete_activities_profile()
    {
        $this->missing();
    }

    protected function get_activities_state()
    {
        $this->missing();
    }
    protected function insert_activities_state()
    {
        $this->missing();
    }
    protected function update_activities_state()
    {
        $this->missing();
    }
    protected function delete_activities_state()
    {
        $this->missing();
    }


    // TODO Filter API
    protected function get_filters()
    {
        $this->missing();
    }
    protected function insert_filter()
    {
        $this->missing();
    }
    protected function delete_filter()
    {
        $this->missing();
    }
    protected function update_filter()
    {
        $this->missing();
    }

    // GET statements/filter/filter_id
    protected function get_filter_result()
    {
        $this->missing();
    }

    // TODO Trigger API

    // The trigger API is needed for
    // a trigger has a filter, an operation, and an agent

    // an operation can be a callback URL and a system event, or issuing a new statement
    // system events are equal with callback URLS.

    // system envents are useful on integrated systems that allow cross process messaging
    protected function get_triggers()
    {
        $this->missing();
    }
    protected function insert_trigger()
    {
        $this->missing();
    }
    protected function delete_trigger()
    {
        $this->missing();
    }
    protected function update_trigger()
    {
        $this->missing();
    }

    protected function check_trigger($statement)
    {
        // for the current statement we need to analyse, which filters might apply
        return false;
    }
    protected function call_trigger()
    {}
}

?>
