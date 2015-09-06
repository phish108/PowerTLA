<?php

include_once('RESTling/contrib/Restling.auto.php');
include_once('PowerTLA.auto.php');

define("TLA_VERSION", "0.6");

function initCoreSystem($pwrtlaPath, $lmspath)
{
    set_include_path($lmspath   . PATH_SEPARATOR .
                     get_include_path());

    // include PowerTLA's classes via their autoloaders

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
            $result["lmstype"] = "Ilias";
            break;
        }

        if (file_exists($cwd . "/lib/moodlelib.php"))
        {
            // got a moodle instance
            $result["lmstype"] = "Moodle";
            break;
        }

        // nothin found, move an directory up.
        $cwd = dirname($cwd);
    }

    if (array_key_exists("lmstype", $result))
    {
        initCoreSystem($apath, $cwd);
        $result["lmspath"] = $cwd;

        chdir($cwd); // change to the LMS directory

        // load the VLE specific SystemHandler class.
        require_once('PowerTLA/' . $result["lmstype"] . '/SystemHandler.class.php');

        $vle  = new SystemHandler($cwd);
        $vle->setGuestUser(TLA_GUESTUSER);

        if (!isset($vle))
        {
            initError("Cannot initialize Virtual Learning Environment");
        }
        else {
            $result["vle"] = $vle;
        }
    }
    else
    {
        initError("Cannot find Virtual Learning Environment");
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

function detectLMS()
{
    $vle      = null;
    $vleinfo  = findVLEInstance();
    if ($vleinfo)
    {
        $vle = $vleinfo["vle"];
    }
    return $vle;
}

function getVLEInstanceInformation($path)
{
    $iInfo    = null;
    $vleinfo  = findVLEInstance();

    if ($vleinfo && $vleinfo["vle"])
    {
        $iInfo =$vleinfo["vle"]->apiDefinition($path);

        if (!isset($iInfo))
        {
            initError("Cannot initialize Virtual Learning Environment");
        }
    }

    if (!isset($iInfo))
    {
        $iInfo = array();
    }

    $iInfo["apis"] = array();

    return $iInfo;
}

?>
