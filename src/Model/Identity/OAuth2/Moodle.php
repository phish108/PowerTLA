<?php

namespace PowerTLA\Model\Identity\OAuth2;

class Moodle extends \PowerTLA\Model\Identity\OAuth2
{
    protected function deleteToken($field, $token) {
        // we simply forget about our tokens
        global $DB;

        $attrMap = [];
        $attrMap[$field] = $token;
        $DB->delete_records("pwrtla_oauth_tokens", $attrMap);

        $attrMap = [];
        $attrMap["initial_$field"] = $token;
        $DB->delete_records("pwrtla_oauth_tokens", $attrMap);
    }

    protected function getToken($field, $token) {
        $attrMap = [];
        $attrMap[$field] = $token;
        if($recToken = $DB->get_record("pwrtla_oauth_tokens", $attrMap)) {
            return (array)$recToken;
        }

        $attrMap = [];
        $attrMap["initial_$field"] = $token;
        if($recToken = $DB->get_record("pwrtla_oauth_tokens", $attrMap)) {
            return (array)$recToken;
        }
        throw new \RESTling\Exception\Forbidden();
    }

    private function getKey($attr) {
        global $DB;
        $object = $DB->get_record("pwrtla_oauth_keys", $attr);
        if (!$object) {
            throw new \RESTling\Exception\Forbidden();
        }
        return $object->key;
    }

    protected function getPrivateKey($kid="private") {
        // return my personal global private key from the file system
        return $this->getKey(["kid" => $kid, "azp_id" => null, "token_id" => null]);
    }

    protected function getSharedKey($kid, $jku) {
        $attr = ["token_id" => null];
        if (empty($kid)) {
            $attr["kid"] =  null;
        }
        else {
            $attr["kid"] = $kid;
        }
        if (empty($jku)) {
            $attr['jku'] = null;
        }
        else {
            $attr['jku'] = $jku;
        }

        return $this->getKey($attr);
    }

    protected function getIssuerKey($kid, $iss) {
        return $this->getKey(["kid" => $kid, "token_id" => $iss]);
    }

    protected function verifyIssuer($iss, $kid) {
        global $DB;
        $object = $DB->get_record("pwrtla_oauth_azp", ["id" => $kid, "url" => $iss]);
        if (!$object) {
            throw new \RESTling\Exception\Forbidden();
        }
    }

    protected function findTargetAuthority($azp) {
        global $DB;

        $object = $DB->get_record("pwrtla_oauth_azp", ["url" => $azp]);
        if (!$object) {
            throw new \RESTling\Exception\Forbidden();
        }
        return (array) $object;
    }

    protected function loadState($state) {
        // loads the state Object
        global $DB;

        $stateObj = $DB->get_record("pwrtla_oauth_state", ["id" => $state]);
        if (!$stateObj) {
            throw new \RESTling\Exception\Forbidden();
        }

        return (array)$stateObj;
    }

    protected function grantSecondaryTokens($issuer) {
        // receives an issuer structure as provided by the getToken function
        return [$access_token, $refresh_token, $expires];
    }

    protected function storeToken($aT, $rT, $ex) {
        global $DB;

        $ts = (new DateTime("NOW"))->getTimestamp();
        $ex = $ts + $ex;
        $azpId  = $this->stateInfo["azp_id"];
        $tokeId = $this->stateInfo["token_id"];
        $updateId = $this->stateInfo["refresh_id"];

        $attr = [
            "access_token" => $aT,
            "refresh_token" => $rT,
            "expries" => $ex
        ];

        if (empty($updateId)) {
            // new token
            $attr["initial_access_token"] = $aT;
            $attr["initial_refresh_token"] = $rT;
            $attr["created"] = $ts;
            $attr["azp_id"] = $azpId;

            if (!empty($tokenId)) {
                $attr["parent"] = $tokenId;
            }
            $DB->insert_record("pwrtla_oauth_tokens", $attr);
        }
        else {
            // refresh token
            $attr["id"] = $updateId;
            $DB->update_record("pwrtla_oauth_tokens", $attr);
        }
    }
}

?>
