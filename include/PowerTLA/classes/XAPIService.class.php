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
        $jsonfeed   = array();
        array_push($jsonfeed, array(
            "actor" => "me",
            "verb"  => "coded",
            "object"=> "powertla",
            "time"  => "1233"
        ));

        array_push($jsonfeed, array(
            "actor" => "me",
            "verb"  => "coded",
            "object"=> "powertla",
            "time"  => "1234"
        ));


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
