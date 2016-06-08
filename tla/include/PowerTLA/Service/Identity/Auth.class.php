<?php

namespace PowerTLA\Service\Identity;
use \PowerTLA\Service\BaseService;

/**
 * REMOVE
 */
class Auth extends BaseService
{
    /**
     * @property $mode
     *
     * Path Info Variable for the steps in the process.
     */
    protected $mode;

    public static function apiDefinition($apis, $prefix, $link="auth", $name="")
    {
        return parent::apiDefinition($apis, $prefix, $link, "org.ietf.oauth2");
    }

    /**
     * @method void initializeRun()
     */
    protected function initializeRun()
    {
        $this->log("enter initializeRun of Auth service ");
        // only real service requests are allowed no web-site interaction from other locations
        // Web-sites should use their backend!
        $this->forbidCORS();

        parent::initializeRun();
    }

    /**
     * @method void validateURI()
     */
    protected function validateURI()
    {
        parent::validateURI();

        if($this->status === RESTling::OK)
        {
            $pathArray = explode('/', $this->path_info);
            if (count($pathArray) == 1)
            {
                $this->mode = $pathArray[0];
            }
            else {
                $this->status = RESTling::BAD_URI;
            }
        }
    }

    /**
     * @method void validateHeader()
     */
    protected function validateHeader()
    {
        // do the standard oauth tricks
        if ($this->method === 'DELETE')
        {
            parent::validateHeader();
            return;
        }

        $valFunction = 'validate_' . $this->mode;

        if(method_exists($this, $valFunc))
        {
            call_user_func(array($this, $valFunc)) ;
        }
        else
        {
            $this->log("validation is not allowed for mode " . $this->mode);
            $this->status = RESTling::BAD_HEADER;
        }

        // this is a pre check
        // if we fail at this stage the client needs to start over again
        if ( $this->session->getOAuthState() !== OAUTH_OK )
        {
            $this->status = RESTling::BAD_HEADER;
        }
    }

    protected function prepareOperation()
    {
        $this->operation = strtolower($this->method) . '_' . strtolower($this->mode);
    }

    /**
     * validation helper functions
     */
    protected function validate_request_token()
    {
        $this->session->validateConsumerToken();
    }

    protected function validate_authorize()
    {
        $this->session->validateRequestToken();
    }

    protected function validate_access_token()
    {
        $this->session->verifyRequestToken();
    }

    protected function validate_register()
    {
        // nothing to be done at this point.
        // the registration is the token free first step
    }

    /**
     * @method void grant_requestToken()
     */
    protected function get_request_token()
    {
        // GET BASE_URI/request-token
        $this->log("grant request token");

        if($this->session->getOAuthState() === OAUTH_OK)
        {
            $this->session->generateRequestToken();
            $this->log("send the request token to the client");
            $this->data = $this->session->getRequestToken();
        }
        else {
            $this->bad_request();
        }
    }

    /**
     * @method void  obtain_authorization()
     */
    protected function get_authorize()
    {
        // GET BASE_URI/authorize
        $this->mark();

        if ($this->VLE->isActiveUser())
        {
            // in case the user is already authenticated via the web the session management
            // shoud use user id provided by the standard session management
            $this->session->setUserID($this->VLE->getUserId());
        }

        if($this->session->requestVerified())
        {
            // this happens if the user uses the Web API

            // TODO: check if the service requires the user to verify the access.
            // if ( $this->session->getConsumerVerificationMode() === "auto" )
            //{
                // return verification code to the user
                // this happens in the case the user is already authenticated
                $this->session->generateVerificationCode();
                $this->data = $this->session->getVerificationCode();
                // $this->respond_json_data();
            //}
            //else
            //{
                // verification required
                // if the user has already verified to use the service we automatically grant again
                // if the user has not verified the service the user needs to be forwarded to a
                // location where the verification can be performed.
            //    $this->authentication_required();
            //}
        }
        else
        {
            // if we reach this point the user is not authenticated
            // so we need to ask for authentication
            $this->authentication_required();
        }
    }

    /**
     * @method void authenticate_user()
     */
    protected function post_authorize()
    {
        // POST BASE_URI/authorize
        $this->mark();

        // we need to use the VLE mechanism
        $this->session->verifyUser($_POST['email'], $_POST['credentials']);
        if ( $this->session->requestVerified())
        {
            // if the user credentials were ok we can send the verification code to the frontend
            // it should then proceed and get the access token
            $this->session->generateVerificationCode();
            $this->data = $this->session->getVerificationCode();
        }
        else
        {
            // wrong user name or password
            $this->authentication_required();
        }
    }

    /**
     * @method void grant_accessToken()
     */
    protected function get_access_token()
    {
        // GET BASE_URI/access_token
        $this->mark();
        if ($this->session->requestVerified())
        {
            $this->session->invalidateRequestToken();
            $this->session->generateAccessToken();
            $this->data = $this->session->getAccessToken();
        }
        else
        {
            // we should be more precise what went wrong.
            $this->authentication_required();
        }
    }


    /**
     * @method void invalidateAccessToken()
     *
     * This method removes the current access token from the database.
     * De facto this means the end of the user session.
     *
     * This function always returns an error code.
     */
    protected function delete_access_token()
    {
        // DELETE BASE_URI/access_token
        $this->mark();
        $this->session->invalidateAccessToken();
        $this->authentication_required();
    }


    /**
     * @method void register_service()
     *
     * This method calculates the Consumer Key and Consumer Secret
     * It stores them in the database and then it sends them to the client.
     *
     */
    protected function put_register()
    {
        $this->post_register();
    }

    protected function post_register()
    {
        $this->mark();
        $deviceID = $_PUT["UUID"];
        $appID = $_PUT["APPID"];

        $response=json_encode($this->generateConsumerTokens($appID,$deviceID));
        echo($response);
    }

    /**
     * @return the Consumer key (= app key)
     *
     *
     */


   protected  function generateConsumerTokens($appId, $uuid){

        global $ilDB;

        // creates a new database table for the registration if no one exists yet
        logging(" check if our table is present already ");
        if (!in_array("ui_uihk_xmob_reg",$ilDB->listTables())) {
            logging("create a new table");
            //create table that will store the app keys and any such info in the database
            //ONLY CREATE IF THE TABLE DOES NOT EXIST

            $fields= array(
                    "app_id" => array(
                            'type' => 'text',
                            'length'=> 255
                    ),
                    "uuid" => array(
                            'type' => 'text',
                            'length'=> 255
                    ),
                    "consumer_key" => array(
                            'type' => 'text',
                            'length'=> 255
                    ),
                    "consumer_secret" => array(
                            'type' => 'text',
                            'length'=> 255
                    )
            );

            $ilDB->createTable("isnlc_reg_info",$fields);
        }
        if (in_array("ui_uihk_xmob_reg",$ilDB->listTables())) {
            //if for the specified app id and uuid an client key (= app key) already exists, use this one instead of creating a new one
            $result = $ilDB->query("SELECT consumer_key FROM ui_uihk_xmob_reg WHERE uuid = " .$ilDB->quote($uuid, "text") . " AND app_id =" .$ilDB->quote($appId, "text"));
            $fetch = $ilDB->fetchAssoc($result);
            logging("fetch: " . json_encode($fetch));
            $consumerKey = $fetch["consumer_key"];
            $consumerSecret = $fetch["consumer_secret"];

            //if no consumer
            if ($consumerKey == null && $consumerSecret == null) {

                $randomSeed = rand();
                //$consumerKey = md5($uuid . $appId . $randomSeed);

                // generate consumer key and consumer secret
                $hash= sha1(mt_rand());
                $consumerKey = substr($hash,0, 30);
                $consumerSecret =substr($hash,30,10);

                //store the new client key (= app key) in the database
                $affected_rows= $ilDB->manipulateF("INSERT INTO ui_uihk_xmob_reg (app_id, uuid, consumer_key, consumer_secret) VALUES ".
                        " (%s,%s,%s)",
                        array("text", "text", "text", "text"),
                        array($appId, $uuid, $consumerKey,$consumerSecret));
                // if this fails we must not return the app key

                logging("return consumer tokens " . $consumerKey. " and ".$consumerSecret);
            }
        }
        //return the consumerKey and consumerSecret in an array

        $data= array(
                "consumerKey"=>$consumerKey,
                "consumerSecret"=>$consumerSecret
                );

        return $data;
      }
}

?>
