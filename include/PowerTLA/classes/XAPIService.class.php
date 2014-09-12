<?php
/**
 *
 */
class XAPIService extends VLEService
{
    /**
     * @property $mode
     */
    protected $mode;
    protected $feature;

    protected $filter_id;
    protected $filters;

    public function __construct()
    {
        parent::__construct();
        $this->filters = array(
            "course.questions" => array("id" => "http://mobinaut.io/xapi/filters/course.questions",
                                        "description" => "Filters QTI question statements for one course",
                                        "scope" => array("context.statement.id"), // CHECK: maybe a simpler approach possible?
                                        "query-type" => "XAPI"),
            "course2.questions" => array("id" => "http://mobinaut.io/mobler-cards/filters/course.questions",
                                         "description" => "Filters QTI question statements for one course",
                                         "query-type" => "moblercards",
                                         "scope" => array("context.statement.id"),
                                         "query" => array())
        );
    }

    protected function validateURI()
    {
        parent::validateURI();

        if($this->status === RESTling::OK)
        {
            // process the operands array set by parent::validateURI();
            // the operands array keeps the sequence of the API part of the
            // REQUEST URI and can be therefore used for defining the API
            // functions.
            $this->mode    = array_shift($this->operands);
            $this->feature = array_shift($this->operands);

            // reset the mode and feature for Our filter API
            if (!empty($this->mode)
                && $this->mode == "statements"
                && !empty($this->feature)
                && $this->feature == "filter")
            {
                $this->mode = $this->feature;
                $this->feature = "result";
                $this->filter_id= array_shift($this->operands);
            }
        }
    }

    protected function prepareOperation()
    {
        // This service is quite complex regarding the permitted methods.
        // the prepareOperation() method generates service functions directly from the
        // request.

        // The class provides
        $action_name = strtolower($this->method);

        // translate put and post to insert and update
        switch ($action_name) {
            case "put":
                $action_name = "insert";
                break;
            case "post":
                $action_name = "update";
                break;
            default:
                break;
        }

        if (empty($this->mode)) {
            $action_name .= "_about";
        }
        else {
            $action_name .= "_" . strtolower($this->mode);

            if (!empty($this->feature))
            {
                $action_name .= "_" . strtolower($this->feature);
            }
        }

        $this->operation = $action_name;
        // $this->log("call method " . $action_name);
    }

    protected function validateOperation()
    {
        // this function checks whether the current user is eligible to
        // fetch the related information. validateOperation() covers entry
        // level access control.
        if (!$this->VLE->isActiveUser())
        {
            // in production set the status to not authenticated
            // both, web-based cookie sessions and oauth should be valid

            // $this->status = RESTling::OPERATION_FAILED;
            // $this->authentication_required();
        }
    }

    /**
     * THE ACTUAL API
     **/

    // About resource
    protected function get_about()
    {
        $this->data = array("version" => array("1.0.0"),
                            "extensions" => array("filters" => array("0.0.2")));
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
        // DANGEROUS!
        // this function loads all items from the LRS and dumps it to the client
        // the filter API is more selective with this respect.

        // the feed should be only available to authenticated users or admins
        $jsonfeed   = array();

        $filter = new MCFilter($this);
        $filter->setScope($this->operands);
        $filter->setParams($this->queryParam);

        $jsonfeed = $filter->apply();
        $this->data = $jsonfeed;
    }

    protected function insert_statements()
    {
        // for each statement in the input
        $this->log("insert statement");
        $this->store_single_statement($this->input);
    }
    protected function update_statements()
    {
        // not sure if this is a correct mode

        // for each statement in the input
        // check whether the statement is stored and remove it if this the case.
        $this->log("update statement");
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
    protected function get_filter()
    {
        // $this->missing();
        $this->data["filters"] = $this->filters;
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
        // $this->missing();
        // query-type "moblercards" is a legacy query type to process old moblercards tables

        if ($this->filters[$this->filter_id]["query-type"] == "moblercards")
        {
            $this->log("old mobler cards filter");
            $filter = new MCFilter($this);
        }
        else if ($this->filters[$this->filter_id]["query-type"] == "XAPI")
        {
            $this->log("new XAPI filter");
            $filter = new XAIFilter($this->VLE);
        }

        $filter->addSelector($this->filters[$this->filter_id]);
        $filter->setScope($this->operands);
        $filter->setParams($this->queryParam);

        $lstStatement = $filter->apply();

        if (strlen($filter->lastError()))
        {
            $this->data = array("filter" => $this->filter_id);
            $this->data["message"] = $filter->lastError();
            $this->data["params"] = $filter->getParams();
            $this->not_found();
            return;
        }

        $this->data = $lstStatement;
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
