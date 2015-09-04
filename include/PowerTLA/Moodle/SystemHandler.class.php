<?php

class SystemHandler extends VLEHandler
{
    protected function initLMS($tp)
    {
        //inform Moodle that it will run as a service.
        define('NO_DEBUG_DISPLAY', true);

        // TODO: does this create conflicts?
        // The idea is that MC and the web ui use the same API
        define('AJAX_SCRIPT', true);
        define('WS_SERVER', true);

        // this is OK, because the script now runs in moodle's root direactory
        require('config.php');
        // tons of black magic is happening now
    }
}

?>