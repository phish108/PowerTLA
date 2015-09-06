<?php

class SystemHandler extends VLEHandler
{
    public function __construct($tp)
    {
        // assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.

        parent::__construct($tp, 'Moodle');
    }

    protected function initLMS($tp)
    {
        //inform Moodle that it will run as a service.

        // TODO: does this create conflicts?
        // The idea is that MC and the web ui use the same API
        define('AJAX_SCRIPT', true);
        define('WS_SERVER', true);

        // this is OK, because the script now runs in moodle's root direactory
        require('config.php');
        // tons of black magic is happening now
    }


    public function getUserId()
    {
        global $USER;
        return $USER->id;
    }

    public function isGuestUser()
    {
        global $USER;

        if ($USER &&
            $USER->id &&
            $USER->username != "guest")
        {
            return FALSE;
        }

        return TRUE;
    }
}

?>
