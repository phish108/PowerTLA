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
 
    protected $input;
    
    protected function validateURI()
    {
        parent::validateURI();
        
        if($this->status === RESTService::OK)
        {
            $aPI = explode('/', $this->path_info);
            $this->mode    = $aPI[0];
            $this->feature = $aPI[1];
            
            // reset the mode and feature for the filter API
            if (!empty($this->mode)
                && $this->mode === 'statements'
                && !empty($this->feature)
                && $this->feature === 'filter')
            {
                $this->mode = $aPI[1];
                $this->feature = "result";
                $this->filter_id= $aPI[2];
            }
        }
    }
    
    protected function validateMethod()
    {
        parent::validateMethod();
        
        // build the name of the handler method of the XAPI protocol.
        $action_name = "";
        switch ($this->method)
        {
            case 'GET':
                $action_name = "get_";
                break;
            case 'PUT':
                $action_name = "insert_";
                break;
            case 'POST':
                $action_name = "update_";
                break;
            case 'DELETE':
                $action_name = "delete_";
                break;
            case 'OPTIONS':
                // CORS madness ;)
                break;
            default:
                $this->status = RESTService::BAD_METHOD;
            break;
        }
        
        // This service is quite complex regarding what methods are allowed.
        if ($this->status === RESTService::OK)
        {
            $action_name .= $this->mode;
            
            if (!empty($this->feature))
            {
                $action_name .= "_" . $this->feature;
            }
            
            // Now we have the handler method name in $action_name for the requested XAPI protocol operation.
            
            // checkMethod() does some magic for us and prepares the method handling to call the correct method
            // of the requested XAPI protocol.
            $this->checkMethod($action_name);
        }
        
        if ($this->status === RESTService::OK &&
            ($this->method === "PUT" || $this->method === "POST") )
        {
            $content = file_get_contents("php://input");
	    $data = json_decode($content, true);
            if (!empty($data))
            {
                $this->input = $data;
            }
            else
            {
                $this->status = RESTService::BAD_METHOD;
            }
        }
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
    {}
    
    protected function insert_statements()
    {
        // for each statement in the input
        $this->store_single_statement($statement);
    }
    protected function update_statements()
    {
        // not sure if this is a correct mode
        
        // for each statement in the input
        // check whether the statement is stored and remove it if this the case.
        $this->store_single_statement($statement);        
    }
        
    protected function delete_statements()
    {}
    
    // Agent Document API
    protected function get_agents()
    {}
   
    protected function get_agents_profile()
    {}
    protected function insert_agents_profile()
    {}
    protected function update_agents_profile()
    {}
    protected function delete_agents_profile()
    {}
    
    // Activities Document API
    protected function get_activities()
    {}
    
    protected function get_activities_profile()
    {}
    protected function insert_activities_profile()
    {}
    protected function update_activities_profile()
    {}
    protected function delete_activities_profile()
    {}
    
    protected function get_activities_state()
    {}
    protected function insert_activities_state()
    {}
    protected function update_activities_state()
    {}
    protected function delete_activities_state()
    {}
    
    
    // TODO Filter API
    protected function get_filters()
    {}
    protected function insert_filter()
    {}
    protected function delete_filter()
    {}
    protected function update_filter()
    {}
    
    // GET statements/filter/filter_id
    protected function get_filter_result()
    {}
    
    // TODO Trigger API
    
    // The trigger API is needed for 
    // a trigger has a filter, an operation, and an agent
    
    // an operation can be a callback URL and a system event, or issuing a new statement
    // system events are equal with callback URLS.
    
    // system envents are useful on integrated systems that allow cross process messaging 
    protected function get_triggers()
    {}
    protected function insert_trigger()
    {}
    protected function delete_trigger()
    {}
    protected function update_trigger()
    {}
    
    protected function check_trigger($statement)
    {
        // for the current statement we need to analyse, which filters might apply
        return false;
    }
    protected function call_trigger()
    {}
}

?>