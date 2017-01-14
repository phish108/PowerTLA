<?php

namespace PowerTLA\Model\Identity\OAuth2;

require_once "auth/oauth/lib/tlaSupport.php";

# TODO move all DB handling into the plugin helper

class Moodle extends \PowerTLA\Model\Identity\OAuth2
{
    use OAuthPlugin;

    protected function isActive() {
        // hook for the moodle auth plugin
        return !$this->inactive();
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

    protected function grantSecondaryTokens($issuer, $expires) {
        // receives an issuer structure as provided by the getToken function
        $attr = [];
        $attr["userid"] = $issuer["userid"];
        $attr["azp_id"] = $issuer["azp_id"];
        $attr["parent"] = $issuer["id"];

        $attr["access_token"] = $this->grantInternalToken($issuer["userid"], $expires);

        return $this->generateToken($attr);
    }

    protected function grantAccessTokens($authority, $userid, $expires = 0) {
        // receives an an authority ID and a userid
        $attr = [];
        $attr["userid"] = $userid;
        $attr["azp_id"] = $authority["id"];
        return $this->generateToken($attr);
    }

    private function generateToken($attr, $expires = 0) {
        global $DB;
        $ts = time();

        if ($expires) {
            $ex = $expires - $ts;
        }
        else {
            $ex= 86000;
            $expires = $ts + $ex;
        }

        if (!array_key_exists("access_token", $attr)) {
            $attr["access_token"] = $this->randomString(40);
        }

        $attr["refresh_token"] = $this->randomString(40);
        $attr["expries"] = $ex;
        $attr["created"] = $ts;

        $attr["initial_access_token"]  = $attr["access_token"];
        $attr["initial_refresh_token"] = $attr["refresh_token"];

        $DB->insert_record("pwrtla_oauth_tokens", $attr);

        return [$attr["access_token"], $attr["refresh_token"], $expires];
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

    protected function redirectHome() {
        global $CFG, $SESSION;

        if ($SESSION->wantsurl) {
            $urltogo = $SESSION->wantsurl;
            unset($SESSION->wantsurl);
            // the session will be automatically updated

            $this->redirect($urltogo);
        }
        $this->redirect($CFG->wwwroot);
    }

    protected function handleAttributeMap($user, $claims) {
        global $CFG;

        if (!is_array($user)) {
            $user = (array) $user;
        }

        if (!is_array($claims)) {
            $claims = (array) $claims;
        }

        if (!array_key_exists(["id", $user])) {
            // set the defaults for new users
            $user['timecreated'] = $user['firstaccess'] = $user['lastaccess'] = time();
            $user['confirmed']   = 1;
            $user['policyagreed']   = 1;
            $user['deleted']   = 0;
            $user['suspended']   = 0;
            $user['mnethostid'] = $CFG->mnet_localhost_id;
            $user['interests'] = '';
            $user['password'] = AUTH_PASSWORD_NOT_CACHED;
        }

        $user['deleted']   = 0;
        $user["username"] = $claims['sub'];

        // get attribute map
        // attrmap -> moodlevalue => claim
        $defaultmap = [
            "email" => "email",
            "firstname" => "given_name",
            "lastname" => "family_name",
            "idnumber" => "",
            "icq" => "",
            "skype" => "",
            "yahoo" => "",
            "aim" => "",
            "msn" => "",
            "phone1" => "phone_number",
            "phone2" => "",
            "institution" => "",
            "departement" => "",
            "address" => "address.street_address",
            "city" => "address.city",
            "country" => "",
            "lang" => "locale",
            "url" => "website",
            "middlename" => "middle_name",
            "firstnamephonetic" => "",
            "lastnamephonetic" => "",
            "alternatename" => "nickname"
        ];

        $map = $this->getAttributeMap();
        if (empty($map)) {
            // if no alternative, then use a reasonable default
            $map = $defaultmap;
        }

        $didUpdate = false;
        foreach ($map as $mKey => $cKey) {
            $cs = $claims;
            if (strpos(".", $cKey) !== false) {
                // handle combined claims
                list($pKey, $cKey) = explode(".", $cKey);
                if (array_key_exists($pKey, $cs)) {
                    $cs = $cs[$pKey];
                }
            }

            if (!empty($cs) &&
                !empty($cKey) &&
                array_key_exists($cKey, $cs) &&
                (!array_key_exists($mKey, $user) || $user[$mKey] != $cs[$cKey])) {

                $user[$mKey] = $cs[$cKey];
                $didUpdate = true;
            }
        }

        // authomatically mark the updated time
        if ($didUpdate) {
            $user['timemodified'] = time();
        }
        return $user;
    }
}

?>
