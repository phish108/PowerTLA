<?php
namespace PowerTLA\Moodle\Validator;

use PowerTLA\Validator;

class Session extends BaseValidator
{
    protected function validateLocalSession()
    {
        global $USER;

        if ($USER &&
            $USER->id)
        {
            $this->tokenType = "Session";
            $this->clientId = $USER->id;
            return TRUE;
        }

        return FALSE;
    }

    protected function validateBearerToken()
    {
        global $DB, $USER;

        $userId = 0;

        if ($tokenInfo = $DB->get_record("pwrtla_tokens",
                                     array("token_type"=>"Bearer",
                                           "token_id"  => $this->token)))
        {
            $userId = $tokenInfo->user_id;

            if (isset($userId) && intval($userId) > 0)
            {
                if ($user = $DB->get_record("user",
                                            array("id" => $userId)))
                {
                    $USER = $user;
                    $this->clientId = $tokenInfo->client_id;
                    $this->tokenKey = $this->token;
                    $this->domain   = $tokenInfo->domain;
                    return TRUE;
                }
            }
        }

        $this->log("bad Bearer token");
        return FALSE;
    }

    protected function validateMACToken()
    {
        global $DB, $USER;
        // MAC Tokens are pretty much OAuth1 Tokens
        $token = $this->extractToken();

        $mac    = $token["key"];
        $id     = $token["id"];
        $nonce  = $token["nonce"];
        $domain = $token["domain"];

        if ($tokenInfo = $DB->get_record("pwrtla_tokens",
                                         array("token_type" => "MAC",
                                               "token_id"   => $id,
                                               "domain"     => $domain)))
        {
            $userId = $tokenInfo->user_id;
            $key    = $tokenInfo->token_key;
            $duuid  = $tokenInfo->client_id;
            $domain = $tokenInfo->domain;

            $uri = $this->getRequestURI();

            $tmac = sha1(urlencode($duuid).
                         urlencode($key).
                         urlencode($domain).
                         urlencode($_SERVER['REQUEST_METHOD']).
                         urlencode($uri).
                         urlencode($nonce));

            if ($mac == $tmac &&
                isset($userId) &&
                intval($userId))
            {
                if ($user = $DB->get_record("user",
                                            array("id" => $userId)))
                {
                    $USER = $user;

                    $this->clientId = $tokenInfo->client;
                    $this->tokenKey = $key;
                    $this->domain   = $domain;
                    return TRUE;
                }
            }
        }

        $this->log("bad MAC token");
        return FALSE;
    }

    protected function validateRequestToken()
    {
        global $DB;
        $token = $this->extractToken();

        $uri = $this->getRequestURI();

        $key    = $token["key"];
        $id     = $token["id"];
        $nonce  = $token["nonce"];
        $domain = $token["domain"];

        if ($tokenInfo = $DB->get_record("pwrtla_tokens",
                                         array("token_type" => "Request",
                                               "token_id"   => $id,
                                               "domain"     => $domain)))
        {
            $verify = sha1(urlencode($token["client_id"]) .
                           urlencode($tokenInfo->token_key) .
                           urlencode($domain) .
                           urlencode($_SERVER['REQUEST_METHOD']) .
                           urlencode($uri) .
                           urlencode($hToken["nonce"]));

            if ($key == $verify)
            {
                // match the client
                $this->clientId = $token["client_id"];
                $this->tokenKey = $token["token_key"];
                $this->domain   = $token["domain"];
                return TRUE;
            }
        }

        $this->log("bad Request token");
        return FALSE;
    }
}

?>