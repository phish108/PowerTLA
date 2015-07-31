<?php
/**
 *
 */
class XAPIService extends VLEService
{
    private $statement;
    private $document;

    public static function apiDefinition($prefix)
    {
        return array(
            "name"   => "gov.adlnet.xapi",
            "link" => $prefix . "/lrs.php"
        );
    }

    protected function initializeRun()
    {
        parent::initializeRun();

        $this->VLE->getAuthValidator()->rejectTokenType("Client");

        $this->statement = $this->VLE->getXAPIStatement();
        $this->document  = $this->VLE->getXAPIDocument();
    }

    protected function validateOperation()
    {
        // this function checks whether the current user is eligible to
        // push or fetch the requested information. validateOperation() covers entry
        // level access control.
        if ($this->VLE->isGuestUser())
        {
            // in production set the status to not authenticated
            // both, web-based cookie sessions and oauth should be valid
            $this->status = RESTling::OPERATION_FORBIDDEN;
            $this->authentication_required();
        }
    }

    protected function validateData()
    {
        // get API
        $op = explode("_", $this->operation);
        array_shift($op);
        $api = implode("_", $op);

        $agent    = $this->queryParam["agent"];
        if (isset($agent) && !empty($agent))
        {
            try
            {
                $agentO = json_decode($agent);
                $agentId = $agentO["mbox"];
                if (!isset($agentId) || empty($agentId))
                {
                    $agentId = $agentO["openid"];
                    if ((!isset($agentId) || empty($agentId)) &&
                        isset($agentO["account"]) &&
                        isset($agentO["account"]["homepage"]) &&
                        !empty($agentO["account"]["homepage"]))
                    {
                        $agentId = $agentO["account"]["homepage"];
                        if (!$this->VLE->validateAgent($agentO))
                        {
                            // unknown agent, agents need to have a valid
                            // UserId in the system
                            throw new Exception("BAD DATA");
                        }

                        $this->document->setAgent($agent); // agent must get set
                    }
                    else
                    {
                        // no agent
                        throw new Exception("BAD DATA");
                    }
                }
            }
            catch (Exception $e)
            {
                $this->state = RESTling::BAD_DATA;
            }
        }

        if ($this->status == RESTling::OK)
        {
            $activityId = $this->queryParam["activityId"];

            if (isset($activtyId) && !empty($activityId))
            {
                if ($this->statement->setActivityId($activityId))
                {
                    $this->document->setActivityId($activityId);
                }
                else
                {
                    $this->state = RESTling::BAD_DATA;
                }
            }
        }

        if ($this->status == RESTling::OK)
        {
            $stateId    = $this->queryParam["stateId"];

            if (isset($stateId) && !empty($stateId))
            {
                if (!$this->document->setStateId($stateId))
                {
                    $this->state = RESTling::BAD_DATA;
                }
            }
        }

        // per API ACL
        if ($this->status == RESTling::OK &&
            !$this->VLE->checkAgentProfile($agent))
        {
            $this->log("run acl");
            /**
             * the acl can be only reltive to the context.
             *
             * based on the context the VLEHandler must determine which statements
             * the active user can see.
             *
             * By default a user can see only those statements where it is an actor.
             *
             * The VLE handler may extend this to certain contexts.
             */
        }

        // per API validation
        if ($this->status == RESTling::OK &&
            ($this->operation == "put_statements" ||
             $this->operation == "post_statements"))
        {
            $this->statement->add($this->inputData);
            if (!$this->statement->validate())
            {
                  $this->status = RESTling::BAD_DATA;
            }
        }

        if ($this->status == RESTling::OK &&
            ($api == "activities_state" ||
             $api == "activities_profile" ||
             $api == "agents_profile"))
        {
            $this->setType($api);

            if ($this->method != "PUT" &&
                !$this->document->read())
            {
                $this->state = RESTling::BAD_DATA;
            }

            if (($this->methpd == "PUT" || $this->method == "POST") &&
                !(isset($this->inputData) || empty($this->inputData)))
            {
                $this->state = RESTling::BAD_DATA;
            }
        }
    }

    /**
     * THE ACTUAL API
     **/

    // About resource
    protected function get()
    {
        $this->data = array("version" => array("1.0.3"));
    }

    // Statement API
    protected function get_statements()
    {
        $since = $this->queryParam["since"];

        if (isset($since) && !empty($since))
        {
            $this->statement->setSince($since);
        }
        $this->statement->get();
    }

    protected function put_statements()
    {
        if (!$this->statement->create()) {
            $this->status = RESTling::BAD_DATA;
        }
    }

    protected function post_statements()
    {

        if (!$this->statement->isStream() && $this->statement->update())
        {
            $this->statement->store();
        }
        else
        {
            $this->status = RESTling::BAD_DATA;
        }
    }

    protected function delete_statements()
    {
        $this->statement->invalidate();
    }

    // Agent Document API
    private function getDocument()
    {
        $this->data = $this->document->get();
    }

    private function createDocument()
    {
        $this->document->create($this->inputData);
    }

    private function updateDocument()
    {
        $this->document->update($this->inputData);
    }

    private function deleteDocument()
    {
        $this->missing();
    }

    protected function get_agents()
    {
        // TODO: is this call allowed?
        $this->missing();
    }

    protected function get_agents_profile()
    {
        $this->getDocument();
    }

    protected function put_agents_profile()
    {
        $this->createDocument();
    }

    protected function post_agents_profile()
    {
        $this->updateDocument();
    }

    protected function delete_agents_profile()
    {
        $this->deleteDocument();
    }

    // Activities Document API

    // activity profiles
    protected function get_activities_profile()
    {
        $this->getDocument();
    }

    protected function put_activities_profile()
    {
        $this->createDocument();
    }

    protected function post_activities_profile()
    {
        $this->updateDocument();
    }

    protected function delete_activities_profile()
    {
        $this->deleteDocument();
    }

    // state api

    protected function get_activities_state()
    {
        $this->getDocument();
    }

    protected function put_activities_state()
    {
        $this->createDocument();
    }

    protected function post_activities_state()
    {
        $this->updateDocument();
    }

    protected function delete_activities_state()
    {
        $this->deleteDocument();
    }
}

?>
