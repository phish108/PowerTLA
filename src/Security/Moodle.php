<?php

namespace PowerTLA\Security;

class Moodle extends \PowerTLA\Security {

    public function validateKey($key, $source) {
        $source = ucfirst(strtolower($source));
        $fname = "validateKey$source";

        if (!method_exists($this, $fname)) {
            throw new \RESTling\Exception\Security\SharedKeyValidationUnsupported();
        }

        $this->$fname($key);
    }

    public function translateTokenName($tokenname, $source) {
        if (strtolower($source) === "cookie" &&
            $tokenname === strtolower('session')) {
            return 'MoodleSession'; // this is necessary, so the cookie validation succeeds
        }
        return $tokenname;
    }

    public function validateToken($token, $source) {
        global $DB;

        throw new \RESTling\Exception\Security\TokenValidationUnsupported();
        // this function is called in a Authorization header is present.
        // first check if the token is present
        $dbToken = $DB->get_record('external_tokens', ["token" => $token]);
        $ts = time();

        if ($dbToken) {
            if ($ts > $dbToken->validuntil) {
                // limit moodle what it can do
                $DB->delete_records('external_tokens',     ['token' => $token]);

                // we MUST delete also the oauth token
                $DB->delete_records('pwrtla_oauth_tokens', ["access_token" => $token]);
                throw new \RESTling\Exception\Unauthorized();
            }

            $dbToken->lastaccess = $ts;
            $DB->set_field('external_tokens',
                           'lastaccess',
                           $dbToken->lastaccess,
                           ['id' => $dbToken->id]);
        }
        else {
            // check if we have the token in our internal records (for auth agents)
            $dbToken = $DB->get_record('pwrtla_oauth_tokens', ["access_token" => $token]);
            if (!$dbToken) {
                throw new \RESTling\Exception\Unauthorized();
            }
            if ($ts > $dbToken->expires) {
                $DB->delete_records('pwrtla_oauth_tokens', ["access_token" => $token]);
                throw new \RESTling\Exception\Unauthorized();
            }
        }

        $user = $DB->get_record("user", ["id" => $dbToken->userid, 'deleted' => 0]);

        if (!$user->confirmed || $user->suspended || $user->deleted) {
            throw new \RESTling\Exception\Forbidden();
        }

        \core\session\manager::set_user($user);
    }

    /**
 	 * Pseudo handler for user sessions in ajax scripts.
 	 *
 	 * @param string key - ignored
 	 * @return void
	 */
	protected function verifyKeyCookie($key) {
        global $USER;
        if (!$USER || !$USER->id) {
            throw new \RESTling\Exception\Forbidden();
        }

        if ($USER->username === "guest" ) {
            throw new \RESTling\Exception\Forbidden();
        }
    }
}

?>
