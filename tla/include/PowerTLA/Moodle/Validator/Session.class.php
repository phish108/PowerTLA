<?php
namespace PowerTLA\Moodle\Validator;

/**
 * Validation of moodle sessions
 *
 * During the moodle initialisation
 *
 * Note: Moodle runs PowerTLA as Ajax Services and not as web-services. Therefore no
 * Service token validation is conducted at this point.
 */
class Session extends  \PowerTLA\Validator\BaseValidator
{
    protected function validateLocalSession()
    {
        global $USER;

        if ($USER &&
            $USER->id)
        {
            $this->clientId = $USER->id;
            return TRUE;
        }

        return FALSE;
    }

    protected function findToken($token) {
        global $DB;

        $now = time();

        $recToken = (object)$DB->get_record('external_tokens',
                                             array('token' => $token));

        if (empty($recToken)) {
            // token unknown; respond 403
            // $this->service->forbidden();
            return false;
        }

        if ($recToken->validuntil > 0 &&
            $recToken->validuntil < time()) {
            // token has expired
            // $this->service->authentication_required();
            return false;
        }

        // if we reached this point we assume silently that the users have the
        // rights to use rest services
        $user = (object)$DB->get_record('user',
                                        array('id' => $recToken->userid));

        if (!($user &&
              $user->id))
        {
            // oops, user does not exist.
            // $this->service->forbidden();
            return false;
        }

        $USER = $user;

        global $CFG;

        $this->clientId = $USER->id;

        $DB->set_field('external_tokens',
                       'lastaccess',
                       $now,
                       ['id' => $recToken->id]);
        return true;
    }
}

?>
