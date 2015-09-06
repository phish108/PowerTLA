<?php
class ProfileService extends VLEService
{
    private $provider;

    public static function apiDefinition($prefix, $name="", $link="")
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
        $token = $this->VLE->getSessionValidator()->getTokenInformation();
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
                        $this->data = array("message" => "Missing UserId Token");
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
        if ($token["type"] != "Request")
        {
            $this->log("wrong type: ". $token["type"]);

            $this->status = RESTling::OPERATION_FORBIDDEN;
            $this->data = array("message" => "Bad Token");
        }
        else if (isset($this->inputData) &&
            ($this->inputDataType == "application/json" ||
             $this->inputDataType == "application/x-www-form-urlencoded") &&
            !(array_key_exists("username", $this->inputData) &&
              array_key_exists("challenge", $this->inputData) &&
              !empty($this->inputData["username"]) &&
              !empty($this->inputData["challenge"])))
        {
            $this->status = RESTling::BAD_DATA;
            $this->data = array("message" => "Missing Data");
        }
    }

    private function validateGet($token)
    {
        if ($this->VLE->isGuestUser() ||
            $token["type"] == "Request")
        {
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
        $token = $this->VLE->getSessionValidator()->getTokenInformation();
        $this->data = $this->provider->authenticate($this->inputData, $token);

        if (!isset($this->data))
        {
            $this->authentication_required();
        }
    }

    // logout if we run on a Bearer or MAC Token
    protected function delete()
    {
        $token = $this->VLE->getSessionValidator()->getTokenInformation();
        $this->data = $this->provider->logout($token);
        $this->authentication_required();
    }
}
?>
