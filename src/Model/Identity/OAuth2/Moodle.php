<?php

namespace PowerTLA\Model\Identity\OAuth2;

// load the local plugin support
require_once "auth/oauth/lib/OAuthPlugin.php";

class Moodle extends \PowerTLA\Model\Identity\OAuth2
{
    use OAuthPlugin;

    protected function isActive() {
        // hook for the moodle auth plugin
        return !$this->inactive();
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
        return [$o->azp_id, $o->crypt_key];
    }

    protected function getIssuerKey($kid, $iss) {
        return $this->getKey(["kid" => $kid, "token_id" => $iss])->crypt_key;
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
            $user['timecreated']  = $user['firstaccess'] = $user['lastaccess'] = time();
            $user['confirmed']    = 1;
            $user['policyagreed'] = 1;
            $user['suspended']    = 0;
            $user['mnethostid']   = $CFG->mnet_localhost_id;
            $user['interests']    = '';
            $user['password']     = AUTH_PASSWORD_NOT_CACHED;
        }

        $user['deleted']   = 0; // always reset an account
        $user["username"] = $claims['sub'];

        // get attribute map
        // attrmap -> moodlevalue => claim
        $map = $this->getAttributeMap();

        $didUpdate = false;
        foreach ($map as $mKey => $cKey) {
            $cs = $claims;
            // this trick is needed for handling the address claim
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

    protected function startUserSession() {
        global $USER;
        \core\session\manager::login_user($USER);
    }

    protected function handleUser($userClaims) {
        global $DB, $USER; // NOTE: Moodle, but not plugin specific

        // create or update the user
        $username = $userClaims["sub"];
        if ($user = $DB->get_record("user", ["username" => $username])) {
            // update a user
            $user = $this->handleAttributeMap($user, $userClaims);
            user_update_user($user, false, false);

            $USER = $DB->get_record('user', array('id' => $user['id']));
        }
        else {
            // create a new user
            $user = $this->handleAttributeMap([], $userClaims);
            $user['id'] = user_create_user($user, false, false);
            if ($user['id'] > 0)
            {
                // moodle wants additional profile setups
                $usercontext = context_user::instance($user['id']);

                // Update preferences.
                useredit_update_user_preference($user);

                if (!empty($CFG->usetags)) {
                    useredit_update_interests($user, $user['interests']);
                }

                // Update mail bounces.
                useredit_update_bounces($user, $user);

                // Update forum track preference.
                useredit_update_trackforums($user, $user);

                // Save custom profile fields data.
                profile_save_data($user);

                // Reload from db.
                $usernew = $DB->get_record('user', array('id' => $user['id']));

                // allow Moodle components to respond to the new user.
                core\event\user_created::create_from_userid($usernew->id)->trigger();
            }
        }

        return $user["id"];
    }


    protected function grantInternalToken($userid, $expires) {
        // Internal Tokens from the OAuth perspective are tokens issued by
        // the service, whereas moodle considers tokens that are not used as
        // sessions as external.
        return external_generate_token(EXTERNAL_TOKEN_PERMANENT,
                                       $this->service->id,
                                       $userid,
                                       context_system::instance(),
                                       $expires,
                                       '');
    }
}

?>
