<?php

class IdentityProvider extends IDPBase
{
    public function authenticate($credentials, $clientToken)
    {
        return null;
    }

    public function logout($clientToken)
    {}


    protected function generateBearerToken($clientToken)
    {}

    protected function generateMACToken($clientToken)
    {}

    protected function storeIdentityToken($userid, $token)
    {
        global $DB;

        $DB->insert_record("pwrtla_usertokens",
                           array("user_id" => $userid,
                                 "user_token" => $token));
    }

    private function checkAccessPin($expirytime=0) {}
    public function generateAccessPin() {}

    public function getIdentityByToken($idToken)
    {
        return $this->findUserByToken($idToken);
    }

    public function getIdentityById($userid=0)
    {
        return $this->findUSerByID($userid);
    }

    public function getUserDetails()
    {
        global $USER;

        if ($USER->username != "guest")
        {
            $tData = $this->loadUserToken($USER->id);
            $retval = $this->makeUserInfo($USER, $tData);
        }

        return $retval;
    }

/**
     * Load function to handle Identities of *different* users
     */
    public function findUserByID($userid)
    {
        global $DB;
        $retval = null;
        // first verify the userid
        if ($user = $DB->get_record("user",
                                    array("id" => $userid)))
        {
            $tData = $this->loadUserToken($userid);
            $retval = $this->makeUserInfo($user, $tData);
        }
        return $retval;
    }

    public function findUserByMail($usermail)
    {
        global $DB;
        $retval = null;
        if ($user = $DB->get_record("user",
                                    array("email" => $usermail)))
        {
            $tData = $this->loadUserToken($user->id);
            $retval = $this->makeUserInfo($user, $tData);
        }
        return $retval;
    }

    public function findUserByToken($idtoken)
    {
        $retval = null;
        $tData = $this->loadTokenUser($idtoken);
        if (isset($tData) && array_key_exists("id", $tData))
        {
            if ($user = $DB->get_record("user",
                                        array("id" => $tData["id"])))
            {
                // load the rest
                $retval = $this->makeUserInfo($user, $tData);
            }
        }
        return $retval;
    }

    public function findUserByLogin($loginname)
    {
        global $DB;
        if ($user = $DB->get_record("user",
                                    array("username" => $loginname)))
        {
            $tData = $this->loadUserToken($user->id);
            $retval = $this->makeUserInfo($user, $tData);
        }

        return $retval;
    }

    public function findUserByHomepage($homepage)
    {
        global $DB;
        // Ilias has no homepage profile
        if (isset($homepage) &&
            !empty($homepage))
        {
            if ($user = $DB->get_record("user",
                                        array("url" => $homepage)))
            {
                $tData = $this->loadUserToken($user->id);
                $retval = $this->makeUserInfo($user, $tData);
            }
        }
        return $retval;
    }

    protected function loadUserToken($userid)
    {
        if (isset($userid) && $userid > 0)
        {
            $tokenData = $this->loadTokenData(array("user_id" => $userid));
            if (!isset($tokenData))
            {
                $tokenData = array("id"=> $userid);
                $tokenData["token"] = $this->createIdentityToken($userid);
            }

            return $tokenData;
        }
        return null;
    }

    protected function loadTokenUser($idtoken)
    {
        if (isset($idtoken) &&
            !empty($idtoken))
        {
            return $this->loadTokenData(array("user_token" => $idtoken));
        }
        return null;
    }

    private function makeUserInfo($oUser, $tData)
    {
        return array(
            "name"       => $oUser->firstname . " " . $oUser->lastname,
            "id"         => $tData["token"],
            "login"      => $oUser->username,
            "email"      => $oUser->email,
            "givenname"  => $oUser->firstname,
            "familyname" => $oUser->lastname,
            "language"   => $oUser->lang
        );
    }

    private function loadTokenData($whereO)
    {
        global $DB;

        if ($ti =  $DB->get_record("pwrtla_usertokens",
                                   $whereO))
        {
            return array(
                "id" => $ti->user_id,
                "token"=> $ti->user_token
            );
        }
        return null;
    }
}
?>
