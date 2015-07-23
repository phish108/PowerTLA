<?php

date_default_timezone_set("Europe/Zurich");

function findIliasInstance()
{
    $cwd = explode('/', getcwd());
    array_pop($cwd);
    $cdpath = implode("/", $cwd);

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
            set_include_path($cdpath . "/". $ipath . PATH_SEPARATOR .
                             $cdpath. PATH_SEPARATOR .
                             // $cdpath . "/". $ipath . "/PowerTLA". PATH_SEPARATOR .
                             get_include_path());

            chdir($cdpath); // change to the Ilias directory

            // now we can include the autoloaders
            include_once('RESTling/contrib/Restling.auto.php');
            include_once('PowerTLA/PowerTLA.auto.php');

            global $powertlapath;

            // and the ilias handler
            require_once('PowerTLA/Ilias/IliasHandler.class.php');
            include_once("PowerTLA/PowerTLA.ini");

            $VLEAPI  = new IliasHandler($cdpath . "/". $ipath );
            $VLEAPI->setGuestUser($guestuser);

            return $VLEAPI;
        }

        if (!empty($ipath))
        {
            $ipath = $p . "/" . $ipath;
        }

        $cdpath = implode("/", $cwd);
    }
    return null;
}

?>
