<?php
/**
 *
 */
class XAPIService extends VLEService
{
    private $lrs;

    public static function apiDefinition($prefix, $name="", $link="")
    {
        return parent::apiDefinition($prefix, "gov.adlnet.xapi", "lrs.php");
    }

    protected function initializeRun()
    {
        parent::initializeRun();

        $this->VLE->getSessionValidator()->rejectTokenType("Client");
        $this->lrs = $this->VLE->getLRS();
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
        $op  = explode("_", $this->operation);
        $m   = array_shift($op);
        $api = implode("_", $op);

        $agent    = $this->queryParam["agent"];
        $this->log("agent param is " . $agent);

        if (isset($agent) && !empty($agent))
        {
            try
            {
                $agentO = json_decode($agent, true);
                $agentInfo = $this->VLE->validateAgent($agentO);
                if (!isset($agentInfo))
                {
                    // unknown agent, agents need to have a valid
                    // UserId in the system
                    throw new Exception("BAD DATA");
                }
            }
            catch (Exception $e)
            {
                $this->state = RESTling::BAD_DATA;
            }
        }

        // per API ACL
        if ($this->status == RESTling::OK &&
            !$this->VLE->checkAgentProfile($agentInfo))
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

        if ($this->status == RESTling::OK)
        {
            $this->log("verify functional query params for " . $api);
            /**
             * verify that the different operations are valid
             */

            if($op == "put_statements")
            {
                if (!$this->inputData["id"])
                {
                    $this->state = RESTling::BAD_DATA;
                }
            }
            else if ($op == "post_statements")
            {
                if ($this->queryParam["statementId"])
                {
                    if (!$this->inputData["id"])
                    {
                        $this->state = RESTling::BAD_DATA;
                    }
                }
                else if ($this->inputData["id"])
                {
                    $this->state = RESTling::BAD_DATA;
                }
            }
            else if ($op == "delete_statements")
            {
                if (!$this->queryParam["statementId"])
                {
                    $this->state = RESTling::BAD_DATA;
                }
            }
            else if ($op != "get")
            {
                // in this case the document api is requested
                $this->queryParam["doctype"] = $api;

                switch ($api)
                {
                    case "activities_profile":
                        if (!$this->queryParam["activityId"] ||
                            ($m != "get" &&
                             !$this->queryParam["profileId"]))
                        {
                            $this->state = RESTling::BAD_DATA;
                        }
                        break;
                    case "activities_state":
                        if (!$this->queryParam["activityId"] &&
                            !$this->queryParam["agent"])
                        {
                            $this->state = RESTling::BAD_DATA;
                        }
                        break;
                    case "agent_profile":
                        if (!$this->queryParam["agent"] ||
                            ($m != "get" &&
                             !$this->queryParam["profileId"]))
                        {
                            $this->state = RESTling::BAD_DATA;
                        }
                        break;
                    case "statements":
                        break;
                    default:
                        $this->state = RESTling::BAD_DATA;
                        break;
                }
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
        // get the activity stream
        $this->log("get statements");
        if ($this->queryParam["statementId"])
        {
            $this->lrs->getAction($this->queryParam["statementId"]);
        }
        else
        {
            $this->data = $this->lrs->getStream($this->queryParam);
        }
    }

    protected function put_statements()
    {
        $this->data = $this->lrs->handleStatement($this->inputData);
    }

    protected function post_statements()
    {
        if ($this->queryParam["statementId"])
        {
            $this->lrs->extendStatement($this->inputData);
        }
        else
        {
            $this->log("process incoming stream");
            $this->data = $this->lrs->processStatementStream($this->inputData);
        }
    }

    protected function delete_statements()
    {
        $this->lrs->deleteStatement($this->queryParam["statementId"]);
    }

    // Agent Document API
    private function getDocument()
    {
        if ($type == "state")
        {
            if (!$this->queryParam["stateId"])
            {
                $this->data = $this->lrs->getDocumentList($this->queryParam);
            }
            else
            {
                $this->data = $this->lrs->getDocument($this->queryParam);
            }
        }
        else if (!$this->queryParam["profileId"])
        {
            $this->data = $this->lrs->getDocumentList($this->queryParam);
        }
        else
        {
            $this->data = $this->lrs->getDocument($this->queryParam);
        }
    }

    private function createDocument()
    {
        if (!$this->lrs->createDocument($this->inputData,
                                        $this->queryParam))
        {
            $this->state = RESTling::BAD_DATA;
        }
    }

    private function updateDocument()
    {
        if (!$this->lrs->storeDocument($this->inputData,
                                       $this->queryParam))
        {
            $this->state = RESTling::BAD_DATA;
        }
    }

    private function deleteDocument()
    {
        if (!$this->lrs->removeDocument($this->inputData,
                                        $this->queryParam))
        {
            $this->state = RESTling::BAD_DATA;
        }
    }

    protected function get_agents_profile()
    {
        $this->getDocument();
    }

    protected function put_agents_profile()
    {
        $this->createtDocument();
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
        $this->createtDocument();
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
        $this->createtDocument();
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
