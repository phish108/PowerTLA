<?php

function findIliasInstance()
{
    $cwd = dirname(__FILE__);

    // Power TLA paths
    $apath = "";

    while (!empty($cwd) && $cwd !== "/")
    {
//        $cdpath = implode("/", $cwd);

        if (empty($apath) && file_exists($cwd . "/include"))
        {
            $apath = $cwd . "/include";
        }

        if (file_exists($cwd . "/include/inc.ilias_version.php"))
        {
            // got an ilias instance
            // set include PowerTLA and Ilias paths

            set_include_path($apath . PATH_SEPARATOR .
                             $cwd . PATH_SEPARATOR .
                             // $cdpath . "/". $ipath . "/PowerTLA". PATH_SEPARATOR .
                             get_include_path());

            chdir($cwd); // change to the LMS directory
            return $apath;
        }

        $cwd = dirname($cwd);
    }
    return null;
}

function findMoodleInstance()
{
    return null;
}

function tearUpIlias()
{
    $lmsPath = findIliasInstance();
    if (isset($lmsPath) && !empty($lmsPath))
    {
        require_once('PowerTLA/Ilias/IliasHandler.class.php');

        $VLEAPI  = new IliasHandler($lmsPath);
        $VLEAPI->setGuestUser(TLA_GUESTUSER);

        return $VLEAPI;
    }
    return null;
}

function tearUpMoodle()
{
    $lmsPath = findMoodleInstance();
    if (isset($lmsPath) && !empty($lmsPath))
    {
        require_once('PowerTLA/Moodle/MoodleHandler.class.php');

        $VLEAPI  = new MoodleHandler($lmsPath);
        $VLEAPI->setGuestUser($guestuser);

        return $VLEAPI;
    }
    return null;
}

function detectLMS()
{
    include_once("PowerTLA/PowerTLA.ini");
    date_default_timezone_set(TLA_TIMEZONE);

    $vle = null;

    // now we must include the autoloaders
    include_once('RESTling/contrib/Restling.auto.php');
    include_once('PowerTLA/PowerTLA.auto.php');

    $vle = tearUpIlias();
    if (!isset($vle))
    {
        $vle = tearUpMoodle();
    }

    return $vle;
}

function getVLEInstanceInformation($path)
{
    include_once("PowerTLA/PowerTLA.ini");
    date_default_timezone_set(TLA_TIMEZONE);

    // we can include the autoloaders, cos the rsd scripts set the include path
    include_once('RESTling/contrib/Restling.auto.php');
    include_once('PowerTLA/PowerTLA.auto.php');

    $iInfo = getIliasInstanceInformation($path);
    if (!isset($iInfo))
    {
        $iInfo = getMoodleInstanceInformation($path);
    }
    if (!isset($iInfo))
    {
        $iInfo = array();
    }

    $iInfo["apis"] = array();

    return $iInfo;
}

function getIliasInstanceInformation($path)
{
    $lmsPath = findIliasInstance();
    if (isset($lmsPath) && !empty($lmsPath))
    {
        require_once('PowerTLA/Ilias/IliasHandler.class.php');
        return IliasHandler::apiDefinition($lmsPath, $path);
    }
    return null;
}

function getMoodleInstanceInformation($path)
{
    return null;
}

?>
