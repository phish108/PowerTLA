<?php

/**
 * @class VLEValidator
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
class VLEValidator extends RESTlingValidator
{
    protected $token;
    protected $tokenType;

    public function setToken($token)
    {
        if (isset($token) && !empty($token))
        {
            $this->accessToken = $token;
        }
    }

    public function setTokenType($tokenType)
    {
        if (isset($tokenType) && !empty($tokenType))
        {
            $this->tokenType = $tokenType;
        }
    }

    public function validate()
    {
        if (isset($this->token) &&
            $this->validateToken())
        {
            return TRUE;
        }
        return $this->validateLocalSession();
    }

    protected function validateToken()
    {
        switch ($this->tokenType)
        {
            case "Bearer":
                return $this->validateBearerToken();
                break;
            case "MAC":
                return $this->validateMACToken();
                break;
            case "Client":
                return $this->validateClientToken();
                break;
            default:
                break;
        }
        return FALSE;
    }

    protected function validateLocalSession()
    {
        return TRUE;
    }

    protected function validateBearerToken()
    {
        return TRUE;
    }

    protected function validateMACToken()
    {
        return TRUE;
    }

    protected function validateClientToken()
    {
        return TRUE;
    }

    protected function extractMACToken()
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
