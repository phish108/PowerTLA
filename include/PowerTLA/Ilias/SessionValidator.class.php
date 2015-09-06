<?php

class SessionValidator extends VLEValidator
{
    private $clientId;
    private $domain;
    private $tokenKey;

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

    protected function validateLocalSession()
    {
        global $ilUser;

        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous")
        {
            $this->tokenType = "Session";
            $this->clientId = $ilUser->getId();
            return TRUE;
        }
        $this->log("bad Session " . $ilUser->getId());
        return FALSE;
    }

    protected function validateBearerToken()
    {
        global $ilDB, $ilUser;

        $userId = 0;
        $result = $ilDB->query("SELECT user_id, domain, client_id FROM pwrtla_tokens WHERE token_type='Bearer' AND token_id = " .
                               $ilDB->quote($this->token, "text"));
		$userIdArray = $ilDB->fetchAssoc($result);
		$userId = $userIdArray["user_id"];

        if (isset($userId) && intval($userId))
        {
            $ilUser->setId($userId);
            $ilUser->read();

            if($ilUser->getLogin())
            {
                $this->clientId = $userIdArray["client_id"];
                $this->tokenKey = $this->token;
                $this->domain   = $userIdArray["domain"];
                return TRUE;
            }
        }
        $this->log("bad Bearer token");

        return FALSE;
    }

    protected function validateMACToken()
    {
        global $ilDB;
        // MAC Tokens are pretty much OAuth1 Tokens
        $token = $this->extractToken();

        $mac = $token["key"];
        $id  = $token["id"];
        $nonce = $token["nonce"];

        $q = "SELECT user_id, token_key, client_id, domain FROM pwrtla_tokens WHERE token_type = 'MAC' AND token_id = %s AND domain = %s";
        $result = $ilDB->queryF($q, array("text", "text"), array($id, $token["domain"]));

        // we should verify that the nonce is not reused.

        $userIdArray = $ilDB->fetchAssoc($result);

        $userId = $userIdArray["user_id"];
        $key = $userIdArray["token_key"];
        $duuid = $userIdArray["client_id"];
        $domain = $userIdArray["domain"];

        $uri = "http" . ($_SERVER["HTTPS"]? "s": "") . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        // remove potential query string
        // $uri =

        $tmac = sha1(implode("", array($duuid, $key, $domain, $_SERVER['REQUEST_METHOD'], $uri, $nonce)));
        if ($mac == $tmac && isset($userId) && intval($userId))
        {
            $ilUser->setId($userId);
            $ilUser->read();

            if($ilUser->getLogin())
            {
                $this->clientId = $userIdArray["client"];
                $this->tokenKey = $userIdArray["token_key"];
                $this->domain   = $userIdArray["domain"];
                return TRUE;
            }
        }

        $this->log("bad MAC token");

        return FALSE;
    }

    protected function validateRequestToken()
    {
        global $ilDB;
        $hToken = $this->extractToken();

        $uri = "http" . ($_SERVER["HTTPS"]? "s": "") . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        $q = "SELECT token_type, token_id, token_key, client_id, domain, extra FROM pwrtla_tokens WHERE token_type = 'Request' AND domain = %s AND token_id = %s";


        $res = $ilDB->queryF($q, array("text","text"), array($hToken["domain"], $hToken["id"]));
        $token = $ilDB->fetchAssoc($res);

        $verify = sha1(urlencode($token["client_id"]) .
                       urlencode($token["token_key"]) .
                       urlencode($token["domain"]) .
                       urlencode($_SERVER['REQUEST_METHOD']) .
                       urlencode($uri) .
                       urlencode($hToken["nonce"]));


        if ($hToken["key"] == $verify)
        {
            // match the client
            $this->clientId = $token["client_id"];
            $this->tokenKey = $token["token_key"];
            $this->domain   = $token["domain"];
            return TRUE;
        }
        // used during authentication
        $this->log("bad Request token");
        return FALSE;
    }
}
?>
