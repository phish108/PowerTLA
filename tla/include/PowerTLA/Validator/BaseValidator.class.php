<?php

namespace PowerTLA\Validator;

/**
 * @class VLEValidator
 *
 * ATTENTION! DON'T USE THIS VALIDATOR
 *
 * The VLEValidator class validates whether a request to a VLEService
 * should be granted. The VLEValidator integrates two validator components.
 *
 * 1. The local Session Validation
 * 2. OAuth Header Validation
 *
 * The local session validation is used for the standard web-based interactions
 * with the VLE.
 *
 * the OAuth Header Validation is typically used by client systems that cannot
 * work in the standard web-based session mode.
 *
 * The OAuth Header Validation may support both Bearer as well as MAC tokens.
 *
 * During the Authentication phase, the validator may also need to validate
 * a Client token.
 */
abstract class BaseValidator extends \RESTling\Validator
{
    protected $token;
    protected $tokenType;
    protected $rejectTypes;

    protected $clientId;
    protected $domain;
    protected $tokenKey;

    abstract protected function validateLocalSession();
    abstract protected function findToken($token);
//    abstract protected function validateBearerToken();
    // abstract protected function validateMACToken(); // obsolete

    protected function getRequestURI()
    {
        return "http"
            . ($_SERVER["HTTPS"]? "s": "")
            . "://" . $_SERVER["SERVER_NAME"]
            . $_SERVER["REQUEST_URI"];
    }

    public function getTokenInformation()
    {
        if (isset($this->clientId) &&
            isset($this->tokenType))
        {
            return array(
                "client" => $this->clientId,
                "token"  => $this->tokenKey,
                "domain" => $this->domain,
                "type"   => $this->tokenType
            );
        }
        return null;
    }

    public function rejectTokenType($tokenType)
    {
        if (!isset($this->rejectTypes))
        {
            $this->rejectTypes = array();
        }
        $this->rejectTypes[] = $tokenType;
    }

    public function setToken($token)
    {
        if (!empty($token))
        {
            $this->token = $token;
        }
    }

    public function setTokenType($tokenType)
    {
        if (!empty($tokenType))
        {
            $this->tokenType = $tokenType;
        }
    }

    public function validate()
    {
        return $this->validateToken() || $this->validateLocalSession();
    }

    protected function validateToken() {

        $headers = getallheaders();

        if (empty($headers)) {
            // should never happen
            return false;
        }

        if (!array_key_exists("Authorization", $headers) ||
            empty($headers["Authorization"]))
        {
            return false;
        }

        $aHeadElems = explode(' ',  $headers["Authorization"]);

        if ($aHeadElems[0] != "Bearer") {
            return false;
        }

        $this->token  = $aHeadElems[1];

        if (!$this->findToken($this->token)) {
            return FALSE;
        }

        return TRUE;
    }

    protected function extractToken()
    {
        $retval = array();

        $aTokenItems = explode(',', $this->token);
        foreach ($aTokenItems as $item)
        {
            $iKO = explode("=", $item);
            $retval[$iKO[0]] = $iKO[1];
        }

        return $retval;
    }

}
?>
