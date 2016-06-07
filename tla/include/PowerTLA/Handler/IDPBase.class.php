<?php
namespace PowerTLA\Handler;

abstract class IDPBase extends \RESTling\Logger
{
    protected $guestuser;
    protected $idToken;

    public function __construct($guestuserid)
    {
        $this->guestuser = $guestuserid;
    }

    abstract protected function generateBearerToken($token);
    abstract protected function generateMACToken($token);
    abstract protected function storeIdentityToken($userid, $token);

    abstract public function authenticate($credentials, $token);
    abstract public function logout($token);

    abstract public function isGuestUser();
    abstract public function getUserId();

    abstract public function findUserByToken($token);
    abstract public function findUserById($token);

    public function getUserDetails()
    {
        if (!$this->isGuestUser())
        {
            $retval = $this->getIdentityById($this->getUserId());
        }

        return $retval;
    }


    public function getIdentityByToken($idToken)
    {
         $rv = $this->findUserByToken($idToken);
         unset($rv["_system"]);
         return $rv;
    }

    public function getIdentityById($userid=0)
    {
        $rv = $this->findUSerByID($userid);
        unset($rv["_system"]);
        return $rv;
    }


    protected function randomString($length=0)
    {
        if ($length == 0)
        {
            $length = 10;
        }
        $resstring = "";
        $chars = "abcdefghijklmnopqrstuvwxyz-ABCDEFGHIJKLNOPQRSTUVWXYZ.1234567890";
        $len = strlen($chars);
        for ($i = 0; $i < $length; $i++)
        {
            $x = rand(0, $len-1);
            $resstring .= substr($chars, $x, 1);
        }
        return $resstring;
    }

    protected function generateAccessToken($clientToken)
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
                $this->log("bad token type? " . TLA_TOKENTYPE);
                break;
        }
        return null;
    }

    public function createIdentityToken($userid)
    {
        $token = $this->randomString(7);
        $this->storeIdentityToken($userid,
                                  $token);
        return $token;
    }
}
?>