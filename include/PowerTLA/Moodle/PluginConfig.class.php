<?php

class PluginConfig extends PluginBase
{
    public function getEngine($tlapath)
    {
        /**
         * At the point this function is called, moodle should be up and running.
         */
        global $CFG;
        include($CFG->dirroot . "/version.php");

        $requrl = $this->buildExternalSystemUrl($tlapath);

        $retval = array(
            "version" => $release,
            "type"=> "Moodle",
            "link"=> $requrl, // official link
            "servicelink" => $requrl . $tlapath . "/"
        );

        return $retval;
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