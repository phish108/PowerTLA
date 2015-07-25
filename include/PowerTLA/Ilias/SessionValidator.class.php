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
        $result = $ilDB->query("SELECT user_id, domain, client FROM pwrtla_tokens WHERE token_type='Bearer' AND token_id = " .
                               $ilDB->quote($this->token, "text"));
		$userIdArray = $ilDB->fetchAssoc($result);
		$userId = $userIdArray["user_id"];

        if (isset($userId) && intval($userId))
        {
            $ilUser->setId($userId);
            $ilUser->read();

            if($ilUser->getLogin())
            {
                $this->clientId = $userIdArray["client"];
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
        // MAC Tokens are pretty much OAuth1 Tokens
        $token = $this->extractToken();

        $mac = $token["key"];
        $id  = $token["id"];
        $nonce = $token["nonce"];

        $q = "SELECT user_id, token_key, client, domain FROM pwrtla_tokens WHERE token_type = 'MAC' AND token_id = %s AND domain = %s";
        $result = $ilDB->queryF($q, array("text", "text"), array($id, $token["domain"]));

        // we should verify that the nonce is not reused.

        $userIdArray = $ilDB->fetchAssoc($result);

        $userId = $userIdArray["user_id"];
        $key = $userIdArray["token_key"];
        $duuid = $userIdArray["client"];
        $domain = $userIdArray["domain"];

        $uri = "http" . ($_SERVER["HTTPS"]? "s": "") . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        // remove potential query string
        // $uri =

        $tmac = sha1(implode("", array($duuid, $key, $domain, $uri, $nonce)));
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

    protected function validateClientToken()
    {
        $hToken = $this->extractToken();

        $uri = "http" . ($_SERVER["HTTPS"]? "s": "") . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        $q = "SELECT token_type, token_id, token_key, client, domain, extra FROM pwrtla_tokens WHERE token_type = 'Client' AND domain = %s AND token_id = %s";

        global $ilDB;
        $res = $ilDB->queryF($q, array("text","text"), array($hToken["domain"], $hToken["id"]));
        $token = $ilDB->fetchAssoc($res);

        $verify = sha1($token["client"] . $token["token_key"] . $hToken["domain"] . $url . $nonce);

        if ($token["token_key"] == $verify)
        {
            // match the client
            $this->clientId = $token["client"];
            $this->tokenKey = $token["token_key"];
            $this->domain   = $token["domain"];
            return TRUE;
        }
        // used during authentication
        $this->log("bad Client token");
        return FALSE;
    }
}
?>
