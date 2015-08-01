<?php
class ProfileService extends VLEService
{
    private $provider;

    public static function apiDefinition($prefix)
    {
        return parent::apiDefinition($prefix, "org.ieee.papi", "profile.php");
    }

    protected function initializeRun()
    {
        $this->provider = $this->VLE->getIdentityProvider();
    }

    /**
     * @method validateData()
     *
     * Validates if the requests are OK
     */
    protected function validateData()
    {
        $token = $this->VLE->getAuthValidator()->getTokenInformation();
        if (isset($token))
        {
            switch ($this->operation)
            {
                case "put":
                    $this->validatePut($token);
                    break;
                case "get_user":
                    $this->validateGet($token);
                    if ($this->status === RESTling::OK &&
                        empty($this->path_info))
                    {
                        $this->status = RESTling::BAD_DATA;
                        $this->data = array("message" => "Missing User Token");
                    }
                    break;
                case "delete":
                    if ($this->VLE->isGuestUser() &&
                        ($token["type"] == "Session" || // cannot bypass the VLE
                        $token["type"] == "Client"))
                    {
                        $this->status = RESTling::OPERATION_FORBIDDEN;
                        $this->data = array("message" => "Bad Token");
                    }
                    break;
                default:
                    $this->validateGet($token);
                    break;
            }
        }
        else
        {
            $this->status = RESTling::OPERATION_FORBIDDEN;
            $this->data = array("message" => "Missing Token");
        }
    }

    private function validatePut($token)
    {
        if ($token["type"] != "Client")
        {
            $this->status = RESTling::OPERATION_FORBIDDEN;
            $this->data = array("message" => "Bad Token");
        }
        else if (isset($this->inputData) &&
            ($this->inputDataType == "application/json" ||
             $this->inputDataType == "application/x-www-form-urlencoded") &&
            !(array_key_exists("login", $this->inputData) &&
              array_key_exists("accesskey", $this->inputData) &&
              !empty($this->inputData["login"]) &&
              !empty($this->inputData["accesskey"])))
        {
            $this->status = RESTling::BAD_DATA;
            $this->data = array("message" => "Missing Data");
        }
    }

    private function validateGet($token)
    {
        if ($this->VLE->isGuestUser() ||
            $token["type"] == "Client")
        {
            $this->log("GET FORBIDDEN!?");
            $this->status = RESTling::OPERATION_FORBIDDEN;
            $this->data = array("message" => "Bad Token");
        }
    }

    /**
     * @method get()
     *
     * returns the user profile for the active user
     */
    protected function get()
    {
        $this->mark("get()");
        $this->data = $this->provider->getUserDetails();
    }

    /**
     * @method get_user()
     *
     * returns the user information by token
     */
    protected function get_user()
    {
        $this->mark("get_user()");
        // load an ID token
        $this->data = $this->provider->getIdentityByToken($this->path_info[0]);
    }

    // login
    protected function put()
    {
        $token = $this->VLE->getAuthValidator()->getTokenInformation();
        $this->data = $this->provider->authenticate($this->inputData, $token);
    }

    // logout if we run on a Bearer or MAC Token
    protected function delete()
    {
        $token = $this->VLE->getAuthValidator()->getTokenInformation();
        $this->data = $this->provider->logout($token);
    }
}
?>
