<?php

namespace PowerTLA\Model\Identity\OAuth2;

class Moodle extends \PowerTLA\Model\Identity\OAuth2
{
    protected function isActive() {
        // hook for the moodle auth plugin
        require_once "auth/oauth/lib/tlaSupport.php";

        global $OauthPlugin;

        if (!$OAuthPlugin || $OAuthPlugin->inactive()) {
            throw new \RESTling\Exception\ServiveUnavailable();
        }
    }

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
        return $object;
    }

    protected function getPrivateKey($kid="private") {
        // return my personal global private key from the file system
        return $this->getKey(["kid" => $kid, "azp_id" => null, "token_id" => null])->key;
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

        $o = $this->getKey($attr);
        return [$o->azp_id, $o->key];
    }

    protected function getIssuerKey($kid, $iss) {
        return $this->getKey(["kid" => $kid, "token_id" => $iss])->key;
    }

    protected function verifyIssuer($iss, $id) {
        global $DB;
        $object = $DB->get_record("pwrtla_oauth_azp", ["id" => $kid]);
        if (!$object) {
            throw new \RESTling\Exception\Forbidden();
        }
        if ($object->id != $id) {
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

    protected function storeState($state, $attr) {
        $attr["id"] = $state;
        $DB->insert_record("pwrtla_oauth_state", $attr);
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
        $attr = [];
        $attr["userid"] = $issuer["userid"];
        $attr["azp_id"] = $issuer["userid"];
        $attr["parent"] = $issuer["id"];

        return $this->generateToken($attr);
    }

    protected function grantAccessTokens($authority, $userid) {
        // receives an an authority ID and a userid
        $attr = [];
        $attr["userid"] = $userid;
        $attr["azp_id"] = $authority["id"];
        return $this->generateToken($attr);
    }

    private function generateToken($attr) {
        global $DB;
        $ts = time();

        $access_token = $this->randomString(40);
        $refresh_token = $this->randomString(40);
        $expires = 86000; // this should be configurable

        $ex = $ts + $created;

        $attr["access_token"] = $access_token;
        $attr["refresh_token"] = $refresh_token;
        $attr["expries"] = $ex;
        $attr["created"] = $ts;
        $attr["initial_access_token"] = $access_token;
        $attr["initial_refresh_token"] = $refresh_token;

        return [$access_token, $refresh_token, $expires];
    }

    protected function storeToken($aT, $rT, $ex) {
        global $DB;
        global $USER;

        $ts = time();
        $ex = $ts + $ex;
        if (!empty($this->stateInfo)) { // avoid random errors
            $azpId  = $this->stateInfo["azp_id"];
            $tokenId = $this->stateInfo["token_id"];
            $updateId = $this->stateInfo["refresh_id"];
        }

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
            $attr["userid"] = $USER->id;

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

    protected function handleUser($userClaims) {
        // update or create
        // the plugin function should take care of this because of the mapping
        // the function MUST return an id
        global $OauthPlugin;
        if ($OauthPlugin) {
            return $OauthPlugin->handleUser($userClaims);
        }
        throw new \RESTling\Exception\ServiceUnavailable();
    }


}

?>
