<?php
abstract class IDPBase extends Logger
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