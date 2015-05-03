<?php

date_default_timezone_set("Europe/Zurich");

function findIliasInstance()
{
    $cwd = explode('/', getcwd());
    array_pop($cwd);
    $cdpath = "..";

    $ipath = "";

    while ($p = array_pop($cwd))
    {
        if (empty($ipath) && file_exists($cdpath . "/include"))
        {
            $ipath = "include";
        }

        if (file_exists($cdpath . "/include/inc.ilias_version.php"))
        {
            // got an ilias instance
            // set include paths
            set_include_path("./". $ipath . PATH_SEPARATOR .
                             "./". $ipath . "/PowerTLA". PATH_SEPARATOR .
                             get_include_path());
            chdir($cdpath); // change to the Ilias directory

            // now we can include the autoloaders
            require_once('RESTling/contrib/Restling.auto.php');
            require_once('PowerTLA/PowerTLA.auto.php');

            global $powertlapath;
            error_log("check the global path" . $powertlapath);

                // and the ilias handler
            require_once('PowerTLA/Ilias/IliasHandler.class.php');

            return $ipath;
        }

        if (!empty($ipath))
        {
            $ipath = $p . "/" . $ipath;
        }

        $cdpath .= "/..";
    }
    return "";
}

?>
