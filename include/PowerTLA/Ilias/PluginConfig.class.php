<?php
class PluginConfig extends Logger
{
    public function getAPI($tlapath)
    {
        global $ilClientIniFile;

        $servername = $ilClientIniFile->readVariable('client',
                                                     'description');
        $lang =       $ilClientIniFile->readVariable('language',
                                                     'default');

        $reqpath = $_SERVER["REQUEST_URI"];

        // get rid of any query string garbage
        $reqpath = preg_replace('/\?.*$/',"", $reqpath);

        // get rid of the rsd section
        $reqpath = preg_replace('/\/[\w\d]+\.php$/',"", $reqpath);

        // strip the tla root
        $rcp = preg_replace('/\//', '\\/', $tlapath);
        $reqpath = preg_replace('/' . $rcp . '$/',"", $reqpath);

        $requrl = "http";
        $requrl .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
        $requrl .= $_SERVER["SERVER_NAME"];
        $requrl .= $reqpath;

        $retval = array(
            "engine" => array(
                "version" => ILIAS_VERSION_NUMERIC,
                "type"=> "ILIAS",
                "link"=> $requrl, // official link
                "servicelink" => $requrl . $tlapath . "/"
            ),
            "language" => $lang,
            "tlaversion" => "0.6",
            "logolink" => $requrl . TLA_ICON,
            "name"     => $servername
        );

        return $retval;
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