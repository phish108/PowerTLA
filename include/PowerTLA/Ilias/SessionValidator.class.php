<?php

class SessionValidator extends VLEValidator
{

    protected function validateLocalSession()
    {
        global $ilUser;

        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous")
        {
            return TRUE;
        }
        $this->log("bad Session " . $ilUser->getId());
        return FALSE;
    }

    protected function validateBearerToken()
    {
        global $ilDB, $ilUser;

        $userId = 0;
        $result = $ilDB->query("SELECT user_id FROM pwrtla_tokens WHERE token_type='Bearer' AND token_id = " .
                               $ilDB->quote($this->token, "text"));
		$userIdArray = $ilDB->fetchAssoc($result);
		$userId = $userIdArray["user_id"];

        if (isset($userId) && intval($userId))
        {
            $ilUser->setId($userId);
            $ilUser->read();

            if($ilUser->getLogin())
            {
                return TRUE;
            }
        }
        $this->log("bad Bearer token");

        return FALSE;
    }

    protected function validateMACToken()
    {
        // MAC Tokens are pretty much OAuth1 Tokens
        $token = $this->extractMACToken();

        $mac = $token["mac"];
        $id  = $token["id"];
        $nonce = $token["nonce"];

        $result = $ilDB->query("SELECT user_id, token_key, device FROM pwrtla_tokens WHERE token_type = 'MAC' AND token_id = " .
                               $ilDB->quote($id, "text"));

        // we should verify that the nonce is not reused.

        $userIdArray = $ilDB->fetchAssoc($result);

        $userId = $userIdArray["user_id"];
        $key = $userIdArray["token_key"];
        $duuid = $userIdArray["device"];

        $uri = "http" . ($_SERVER["HTTPS"]? "s": "") . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        $tmac = sha1(implode(",", array($mackey, $duuid, $uri, $nonce)));
        if ($mac == $tmac && isset($userId) && intval($userId))
        {
            $ilUser->setId($userId);
            $ilUser->read();

            if($ilUser->getLogin())
            {
                return TRUE;
            }
        }
        $this->log("bad Bearer token");

        return FALSE;
    }

    protected function validateClientToken()
    {
        // used during authentication
        $this->log("bad Client token");
        return FALSE;
    }

}
?>
