<?php

$localpath = "restservice";

if (!isset($pathprefix))
{
    $pathprefix = "";
}
else {
    $pathprefix .= "/". $localpath;
}

if (!isset($service))
{
    // find ilias instance to load the metadata
    $scwd = getcwd();

    $cwd = explode('/', $scwd);

    $rwd = dirname($_SERVER["REQUEST_URI"]);

    $ipath = "/include";

    if (!file_exists("include/findVLE.php"))
    {
        while ($p = array_pop($cwd))
        {
            $rwd = dirname($rwd);

            if (file_exists(implode('/', $cwd) . $ipath . "/findVLE.php"))
            {
                set_include_path(implode('/', $cwd). $ipath . PATH_SEPARATOR .
                                 implode('/', $cwd). $ipath . "/PowerTLA". PATH_SEPARATOR .
                                 get_include_path());
                break;
            }
        }
    }
    else
    {
        // array_unshift($xpath, $localpath);
        // because THIS script might run via our root rsd.php
        set_include_path(implode('/', $cwd). $ipath . PATH_SEPARATOR .
                         implode('/', $cwd). $ipath . "/PowerTLA". PATH_SEPARATOR .
                         get_include_path());
    }

    require_once("findVLE.php");

    $service = getVLEInstanceInformation($rwd);
}

include_once("learningcards/apis.php");
include_once("content/apis.php");
include_once("identity/apis.php");
include_once("xapi/apis.php");

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);

?>
