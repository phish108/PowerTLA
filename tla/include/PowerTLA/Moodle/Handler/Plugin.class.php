<?php

namespace PowerTLA\Moodle\Handler;
use PowerTLA\Handler\PluginBase;

class Plugin extends PluginBase
{

    public function getEngine()
    {
        return "Moodle";
    }

    public function getDisplayName()
    {
        global $SITE;
        return $SITE->fullname;
    }

    public function getDefaultLanguage()
    {
        global $CFG;
        return $CFG->lang;
    }

    /**
     * provides information on on the activation state/maintenance mode
     */
    public function isActive()
    {
        return TRUE;
    }
}

?>