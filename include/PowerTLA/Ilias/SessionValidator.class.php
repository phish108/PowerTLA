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
        $result = $ilDB->query("SELECT user_id FROM isnlc_auth_info WHERE session_key = " .
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

        $result = $ilDB->query("SELECT user_id, mackey, device FROM isnlc_auth_mac_info WHERE keyid = " .
                               $ilDB->quote($id, "text"));

        // we should verify that the nonce is not reused.

        $userIdArray = $ilDB->fetchAssoc($result);

        $userId = $userIdArray["user_id"];
        $key = $userIdArray["mackey"];
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
