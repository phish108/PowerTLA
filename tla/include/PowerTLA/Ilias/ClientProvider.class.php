<?php

namespace PowerTLA;

class ClientProvider extends \RESTling\Logger
{
    private $clientId;
    private $appId;

    private function randomString($length=10)
    {
        if ($length <= 0)
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

    public function addClient($clientId, $appID)
    {
        $tokenid = $this->randomString(7);

        $tokenkey = $this->randomString(128);
        // $tokenkey = sha1($clientId . $appID . $randomseed);

        global $ilDB;

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "Request"),
            "domain"     => array("text", $appID),
            "token_id"   => array("text", $tokenid),
            "token_key"  => array("text", $tokenkey),
            "client_id"  => array("text", $clientId)
        ));

        return array(
            "type"   => "Request",
            "domain"       => $appID,
            "token"        => $tokenkey,
            "id"           => $tokenid,
            "algorithm"    => "SHA1",
            "sequence"     => array("client", "token", "domain", "method", "url", "nonce"),
            "parameter"    => array("key", "id", "domain", "nonce")
        );
    }

    public function eraseClient($clientId="", $appId="")
    {
        if (empty($clientId))
        {
            $clientId = $this->clientId;
        }
        if (empty($appId))
        {
            $appId = $this->appId;
        }

        if (!empty($clientId) && !empty($appId))
        {
            global $ilDB;
            $q = "DELETE FROM pwrtla_tokens WHERE token_type = 'Request' AND client_id = %s AND domain = %s";
            $ilDB->manipulateF($q, array("text", "text"), array($clientId, $appId));
        }
    }

    public function storeExtraInformation($extraObject, $clientId="", $appId="")
    {
        if (empty($clientId))
        {
            $clientId = $this->clientId;
        }
        if (empty($appId))
        {
            $appId = $this->appId;
        }
        if (!empty($clientId) && !empty($appId))
        {
            $data = json_encode($extraObject);
            $q    = "UPDATE TABLE pwrtla_tokens SET extra = %s WHERE token_type = 'Request' AND domain = %s AND client_id = %s";

            $ilDB->manipulateF($q, array("text", "text", "text"), array($data, $clientId, $appId));
        }
    }
}

?>
