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

    private function generateBearerToken($clientToken)
    {
        global $ilUser, $ilDB;

        $randomseed = random_bytes(10);
        $tokenkey = sha1($clientToken["client"] . $clientToken["domain"] . $randomseed);

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "Bearer"),
            "token_id"   => array("text", $tokenkey),
            "user_id"    => array("int", $ilUser->getID()),
            "client"     => array("int", $clientToken["client"]),
            "domain"     => array("int", $clientToken["domain"]),
        ));

        return array(
            "token_type"   => "Bearer",
            "domain"       => $clientToken["domain"],
            "token"        => $tokenkey
        );
    }

    private function generateMACToken($clientToken)
    {
        global $ilUser, $ilDB;

        $randomseed = random_bytes(10);
        $tid = sha1(random_bytes(10));
        $startid = rand(0, strlen($tid) - 7);

        $tokenid  = substr($tid, $startid, 7);
        $tokenkey = sha1($clientToken["device"] . $clientToken["domain"] . $randomseed);

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "MAC"),
            "token_key"  => array("text", $tokenkey),
            "token_id"   => array("text", $tokenid),
            "domain"     => array("text", $clientToken["domain"]),
            "client"     => array("text", $clientToken["client"]),
            "user_id"    => array("int", $ilUser->getID())
        ));

        return array(
            "token_type"   => "MAC",
            "domain"       => $clientToken["domain"],
            "token"        => $tokenkey,
            "id"           => $tokenid,
            "algorithm"    => "SHA1",
            "sequence"     => array("client", "token", "domain", "url", "nonce"),
            "parameter"    => array("key", "id", "domain", "nonce")
        );
    }

    private function createIdentityToken()
    {
        global $ilUser, $ilDB;

        $tid = sha1($ilUser->getLogin() . random_bytes(10));
        $startid = rand(0, strlen($tid) - 7);
        $tokenid  = substr($tid, $startid, 7);

        $ilDB->insert("pwrtla", array(
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

    public function getIdentity($idToken)
    {
        global $ilUser, $ilDB;

        $retval = null;
        $q = "SELECT user_id FROM pwrtla_ipd WHERE user_token = %s ";
        $types = array("text");
        $values = array($login);
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
            $tid = sha1(random_bytes(25));
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
