<?php

function findIliasInstance()
{
    $cwd = explode('/', getcwd());
    // array_pop($cwd);

    $apath = array();

    while (count($cwd))
    {
        $cdpath = implode("/", $cwd);

        if (empty($apath) && file_exists($cdpath . "/include"))
        {
            array_push($apath, "include");
        }

        if (file_exists($cdpath . "/include/inc.ilias_version.php"))
        {
            // got an ilias instance
            // set include paths
            $ipath = implode("/", $apath);
            set_include_path($cdpath . "/". $ipath . PATH_SEPARATOR .
                             $cdpath. PATH_SEPARATOR .
                             // $cdpath . "/". $ipath . "/PowerTLA". PATH_SEPARATOR .
                             get_include_path());

            chdir($cdpath); // change to the LMS directory
            return $ipath;
        }

        $p = array_pop($cwd);
        if (!empty($apath))
        {
            array_unshift($apath, $p);
        }

        $cdpath = implode("/", $cwd);
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
