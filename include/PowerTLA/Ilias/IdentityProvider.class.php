<?php

class IdentityProvider extends Logger
{
    private $guestuser;

    public function __construct($guestuserid)
    {
        $this->guestuser = $guestuserid;
    }

    public function authenticate($credentials, $clientToken)
    {
        global $ilUser, $ilDB;
        $userId = $ilUser->getUserIdByLogin($credentials["login"]);

        $retval = null;

        if ($userId > 0)
        {
            $ilUser->setId($userId);
            $ilUser->read();

            $passwordHash = $ilUser->getPasswd(); //returns md5-hashed password

            // however, we continue to use SHA1
            $validate = sha1($credentials["login"].
                             $passwordHash .
                             $clientToken["token"]);

            if ($credentials["accesskey"] == $validate)
            {
                $retval = $this->generateAccessToken($clientToken);
            }
            else
            {
                // check PIN
                $now   = time();
                $dtime = 24 * 60 * 60 * 1000; // 1 day expiry time

                $pin = $this->checkAuthPin($now - $dtime);
                if (isset($pin))
                {
                    $validate = sha1($credentials["login"].
                                     $pin .
                                     $clientToken["token"]);
                    if ($credentials["accesskey"] == $validate)
                    {
                        // invalidate the pin
                        $q = "DELETE FROM pwrtla_authpins WHERE login =  %s";
                        $ilDB->manipulateF($q, array("text"), array($credentials["login"]));

                        $retval = $this->generateAccessToken($clientToken);
                    }
                }
            }
        }
        return $retval;
    }

    public function logout($clientToken)
    {
        global $ilUser, $ilDB;
        $token = $clientToken["token"];
        $type  = $clientToken["type"];

        $q = "DELETE FROM pwrtla_tokens WHERE user_id = %s AND ";
        if ($type == "Bearer")
        {
            $q .= "token_id = %s";
        }
        else
        {
            $q .= "token_key = %s";
        }

        $ilDB->manipulateF($q,
                           array("int", "text"),
                           array(
                               $ilUser->getId(),
                               $token
                           ));
    }

    private function generateAccessToken($clientToken)
    {
        switch (TLA_TOKENTYPE)
        {
            case "Bearer":
                return $this->generateBearerToken($clientToken);
                break;
            case "MAC":
                return $this->generateMACToken($clientToken);
                break;
            default:
                break;
        }
        return null;
    }

    private function randomString($length=0)
    {
        if ($length == 0)
        {
            $length = 10;
        }
        $resstring = "";
        $chars = "abcdefghijklmnopqrstuvwxyz-ABCDEFGHIJKLNOPQRSTUVWXYZ.1234567890";
        $len = strlen($chars);
        for ($i = 0; i < $length; i++)
        {
            $x = rand(0, $len-1);
            $resstr .= substr($chars, $x, 1);
        }
        return $resstring;
    }


    private function generateBearerToken($clientToken)
    {
        global $ilUser, $ilDB;

        $randomseed = $this->randomString();
        $tokenkey = sha1($clientToken["client"] . $clientToken["domain"] . $randomseed);

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "Bearer"),
            "token_id"   => array("text", $tokenkey),
            "user_id"    => array("int", $ilUser->getID()),
            "client_id"     => array("int", $clientToken["client"]),
            "domain"     => array("int", $clientToken["domain"]),
        ));

        return array(
            "type"   => "Bearer",
            "domain"       => $clientToken["domain"],
            "token"        => $tokenkey
        );
    }

    private function generateMACToken($clientToken)
    {
        global $ilUser, $ilDB;

        $randomseed = $this->randomString(10);
        $tid = sha1($this->randomString(10));
        $startid = rand(0, strlen($tid) - 7);

        $tokenid  = substr($tid, $startid, 7);
        $tokenkey = sha1($clientToken["device"] . $clientToken["domain"] . $randomseed);

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "MAC"),
            "token_key"  => array("text", $tokenkey),
            "token_id"   => array("text", $tokenid),
            "domain"     => array("text", $clientToken["domain"]),
            "client_id"     => array("text", $clientToken["client"]),
            "user_id"    => array("int", $ilUser->getID())
        ));

        return array(
            "type"   => "MAC",
            "domain"       => $clientToken["domain"],
            "token"        => $tokenkey,
            "id"           => $tokenid,
            "algorithm"    => "SHA1",
            "sequence"     => array("client", "token", "domain", "method", "url", "nonce"),
            "parameter"    => array("key", "id", "domain", "nonce")
        );
    }

    private function createIdentityToken()
    {
        global $ilUser, $ilDB;

        $tid = sha1($ilUser->getLogin() . $this->randomString(10));
        $startid = rand(0, strlen($tid) - 7);
        $tokenid  = substr($tid, $startid, 7);

        $ilDB->insert("pwrtla_usertokens", array(
            "user_id"    => array("int", $ilUser->getId()),
            "user_token" => array("text", $tokenid)
        ));
    }

    private function checkAuthPin($expirytime=0) {
        global $ilUser, $ilDB;

        $retval = null;
        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous" &&
            isset($this->guestuser) &&
            !empty($this->guestuser) &&
            $ilUser->getLogin() != $this->guestuser)
        {
            $login = $ilUser->getLogin();
            $q = "SELECT pinhash, created FROM pwrtla_authpins WHERE login = %s ";
            $types = array("text");
            $values = array($login);
            $res = $ilDB->queryF($q,
                                 array("text"),
                                 array($ilUser->getLogin()));
            $row = $ilDB->fetchAssoc($res);
            if ($expirytime > 0 &&
                intval($row["created"]) > $expirytime)
            {
                // drop the pin and return nothing
                $q = "DELETE FROM pwrtla_authpins WHERE login =  %s";
                $ilDB->manipulateF($q, array("text"), array($login));
            }
            else
            {
                $retval = $row["pinhash"];
            }
        }
        return $retval;
    }

    public function getIdentityByToken($idToken)
    {
        global $ilUser, $ilDB;

        $retval = null;
        $q = "SELECT user_id FROM pwrtla_usertokens WHERE user_token = %s ";
        $types = array("text");
        $values = array($ilUser->getLogin());
        $res = $ilDB->queryF($q,
                             array("text"),
                             array($idToken));
        $row = $ilDB->fetchAssoc($res);
        if (isset($row) && intval($row["user_id"]))
        {
            $ilUser->setId($row["user_id"]);
            $ilUser->read();
            $retval = $this->getUserDetails();
        }
        return $retval;
    }

    public function getIdentityById($userid=0)
    {
        global $ilUser, $ilDB;

        if ($userid)
        {
            $oUser = new ilObjUser($userid);
            $oUser->read();

            if (!$oUser->getLogin())
            {
                return null;
            }
        }
        else
        {
            $oUser = $ilUser;
        }

        $retval = null;
        $q = "SELECT user_id FROM pwrtla_usertokens WHERE user_id = %s ";
        $types = array("int");
        $values = array($oUser->getId());
        $res = $ilDB->queryF($q,
                             $types,
                             $values);
        $row = $ilDB->fetchAssoc($res);
        if (isset($row) && intval($row["user_id"]))
        {
            $ilUser->setId($row["user_id"]);
            $ilUser->read();
            $retval = $this->getUserDetails();
        }
        return $retval;
    }

    public function getUserDetails()
    {
        global $ilUser;
        $retval = null;

        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous" &&
            isset($this->guestuser) &&
            !empty($this->guestuser) &&
            $ilUser->getLogin() != $this->guestuser)
        {
            $retval = array(
                "name"       => $ilUser->getFullname(),
                "id"         => $idToken,
                "login"      => $ilUser->getLogin(),
                "email"      => $ilUser->getEmail(),
                "givenname"  => $ilUser->getFirstName(),
                "familyname" => $ilUser->getLastName(),
                "language"   => $ilUser->getLaguage()
            );
        }

        return $retval;
    }

    public function generateAccessPin() {
        global $ilUser, $ilDB;
        $retval = null;
        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous" &&
            isset($this->guestuser) &&
            !empty($this->guestuser) &&
            $ilUser->getLogin() != $this->guestuser)
        {
            $tid = sha1($this->randomString(10));
            $startid = rand(0, strlen($tid) - 4);
            $accessPin  = substr($tid, $startid, 4);

            $pinhash = sha1($accessPin);

            // check if there is already a PIN
            $now   = time();
            $dtime = 24 * 60 * 60 * 1000; // 1 day expiry time

            $pin = $this->checkAuthPin($now - $dtime);
            if (isset($pin))
            {
                // invalidate the old pin
                $q = "DELETE FROM pwrtla_authpins WHERE login =  %s";
                $ilDB->manipulateF($q, array("text"), array($ilUser->getLogin()));
            }

            // now create the new pin
            $ilDB->insert("pwrtla_authpins", array(
                "login"     => array("text", $ilUser->getLogin()),
                "created"   => array("int", $now),
                "pinhash"   => array("text", $pinhash)
            ));
            $retval = $accessPin;
        }

        return $retval;
    }
}

?>
