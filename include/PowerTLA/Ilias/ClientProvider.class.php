<?php

class ClientProvider extends Logger
{
    private $clientId;
    private $appId;

    public function addClient($clientId, $appID)
    {
        $randomseed = random_bytes(10);
        $tid = sha1(random_bytes(10));
        $startid = rand(0, strlen($tid) - 7);

        $tokenid  = substr($tid, $startid, 7);

        $tokenkey = sha1($clientId . $appId . $randomseed);

        global $ilDB;

        $ilDB->insert("pwrtla_tokens", array(
            "token_type" => array("text", "Request"),
            "domain"     => array("text", $appId),
            "token_id"   => array("text", $tokenid),
            "token_key"  => array("text", $tokenkey),
            "client"     => array("text", $clientId)
        ));

        return array(
            "token_type"   => "Request",
            "domain"       => $appId,
            "token"        => $tokenkey,
            "id"           => $tokenid,
            "algorithm"    => "SHA1",
            "sequence"     => array("client", "token", "domain", "url", "nonce"),
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
            $q = "DELETE FROM pwrtla_tokens WHERE token_type = 'Request' AND client = %s AND domain = %s";
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
            $q    = "UPDATE TABLE pwrtla_tokens SET extra = %s WHERE token_type = 'Request' AND domain = %s AND client = %s";

            $ilDB->manipulateF($q, array("text", "text", "text"), array($data, $clientId, $appId));
        }
    }
}

?>
