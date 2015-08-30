<?php

function initCoreSystem($pwrtlaPath, $lmspath)
{
    set_include_path($pwrtlaPath . PATH_SEPARATOR .
                     $lmspath   . PATH_SEPARATOR .
                     get_include_path());

    // include PowerTLA's classes via their autoloaders
    include_once('RESTling/contrib/Restling.auto.php');
    include_once('PowerTLA/PowerTLA.auto.php');

    include_once("PowerTLA/PowerTLA.ini");
    date_default_timezone_set(TLA_TIMEZONE);
}

function findVLEInstance()
{
    $result = array();

    $cwd = dirname(__FILE__);

    $result["rootpath"] = $cwd;

    // Power TLA paths
    $apath = "";

    while (!empty($cwd) && $cwd !== "/")
    {
        // found PowerTLA include path
        if (empty($apath) && file_exists($cwd . "/include"))
        {
            $apath = $cwd . "/include";
            $result["tlapath"] = $apath;
        }

        if (file_exists($cwd . "/include/inc.ilias_version.php"))
        {
            // got an ilias instance
            // set include PowerTLA and Ilias paths
            initCoreSystem($apath, $cwd);

            $result["lmspath"] = $cwd;
            $result["lmstype"] = "Ilias";
            break;
        }

        if (file_exists($cwd . "/lib/moodlelib.php"))
        {
            // got a moodle instance
            initCoreSystem($apath, $cwd);

            $result["lmspath"] = $cwd;
            $result["lmstype"] = "Moodle";
            break;
        }

        // nothin found, move an directory up.
        $cwd = dirname($cwd);
    }

    return $result;
}

function initError($msg)
{
    if (!isset($msg))
    {
        $msg = "";
    }
    error_log("PowerTLA Init Error: " . $msg);
}

function getVLEInstanceInformation($path)
{
    $iInfo    = null;
    $vleinfo  = findVLEInstance();

    if ($vleinfo &&
        array_key_exists("lmstype", $vleinfo) &&
        array_key_exists("tlapath", $vleinfo))
    {
        chdir($vleinfo["lmspath"]);

        $lmsPath = $vleinfo["lmspath"];
        if (isset($lmsPath) &&
            !empty($lmsPath))
        {
            require_once('PowerTLA/' .
                         $vleinfo["lmstype"] .
                         '/SystemHandler.class.php');

            $iInfo = SystemHandler::apiDefinition($lmsPath, $path);
        }

        if (!isset($iInfo))
        {
            initError("Cannot initialize Virtual Learning Environment");
        }
    }
    else
    {
        initError("Cannot find Virtual Learning Environment");
    }

    if (!isset($iInfo))
    {
        $iInfo = array();
    }

    $iInfo["apis"] = array();

    return $iInfo;
}

?>
