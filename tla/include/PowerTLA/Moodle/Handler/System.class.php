<?php

namespace PowerTLA\Moodle\Handler;

use PowerTLA\Handler\VLE;

class System extends VLE
{
    public function __construct()
    {
        // assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.

        parent::__construct('Moodle');
    }

    protected function initLMS()
    {
        //inform Moodle that it will run as a service.

        // Combined use of AJAX and Webseriveces creates conflicts!
        // The idea is that MC and the web ui use the same API
        define('AJAX_SCRIPT', true);

        // However, as soon as WS_Server is set, browser sessions will not be
        // evaluated. Remove for the time being.
        // define('WS_SERVER', true);

        // this is OK, because the script now runs in moodle's root direactory
        require('config.php');
        // tons of black magic is happening now
    }

    // ignore guest user settings in moodle
    public function setGuestUser($username) {}

    protected function checkPrivileges($context, $privileges)
    {
        require_once 'lib/accesslib.php';

        $mode = 1; // 1 = this user
                   // 2 = other user
                   // 3 = object
                   // 4 = other user privs
        if (property_exists($context, 'object'))
        {
            $mode = 3;
        }
        if (property_exists($context, 'user'))
        {
            $mode += 1;
            $privileges  = $this->initPrivileges(false);
        }

        if (!isset($privileges))
        {
            $privileges = $this->initPrivileges();
        }


         if ($mode >= 3)
        {
            // get read privileges

            // get write privileges

             // get learning progress teacher's privileges

             $priv =  'gradereport/grader:view';
             $priv =  'gradereport/history:view';
         }

        if ($mode === 2)
        {
            // get learning progress reading privileges
            // get learning progress update privileges
        }

        return $privileges;
    }
}

?>
