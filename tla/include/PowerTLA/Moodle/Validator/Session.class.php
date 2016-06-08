<?php
namespace PowerTLA\Moodle\Validator;

use PowerTLA\Validator;

/**
 * Validation of moodle sessions
 *
 * During the moodle initialisation
 *
 * Note: Moodle runs PowerTLA as Ajax Services and not as web-services. Therefore no
 * Service token validation is conducted at this point.
 */
class Session extends BaseValidator
{
    protected function validateLocalSession()
    {
        global $USER;

        if ($USER &&
            $USER->id)
        {
            $this->tokenType = "Session";
            $this->clientId = $USER->id;
            return TRUE;
        }

        return FALSE;
    }
}

?>