<?php

class XAPIService extends VLEService
{

    protected function validateOperation()
    {
        if (!$this->VLESession->active())
        {
            $this->status = RESTling::OPERATION_FORBIDDEN;
        }
        else
        {
            $opa = explode('_', $this->operation);
            $item = $this->path_info[0];

            if ($opa[0] != 'put' && isset($item))
            {
                $this->VLE->validateUUID($item);
            }
        }
    }

    // About resource
    protected function get()
    {
        $this->data = array("version" => array("1.0.0"),
                            "extensions" => array("filters" => array("0.0.2")));
    }

    /**
     * Statement API
     */
    protected function get_statements()
    {
        $this->missing();
    }

    protected function put_statements()
    {
        $this->missing();
    }

    protected function post_statements()
    {
        $this->missing();
    }

    protected function delete_statements()
    {
        $this->missing();
    }

    /**
     * Agent API
     */
    protected function get_agents()
    {
        $this->missing();
    }

    protected function get_agents_profile()
    {
        $this->missing();
    }

    protected function put_agents_profile()
    {
        $this->missing();
    }

    protected function post_agents_profile()
    {
        $this->missing();
    }

    protected function delete_agents_profile()
    {
        $this->missing();
    }

    /**
     * Activity Documents API
     */
    protected function get_activities()
    {
        $this->missing();
    }

    protected function get_activities_profile()
    {
        $this->missing();
    }

    protected function put_activities_profile()
    {
        $this->missing();
    }

    protected function post_activities_profile()
    {
        $this->missing();
    }

    protected function delete_activities_profile()
    {
        $this->missing();
    }
}

?>