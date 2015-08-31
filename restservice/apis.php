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
    // find vle instance to load the metadata
    $rwd = dirname($_SERVER["REQUEST_URI"]);

    $ipath = "/include";
    $cwd = dirname(__FILE__);

    $ipath = "/include";

    while ($cwd != "/")
    {
        if (file_exists($cwd . $ipath . "/findVLE.php"))
        {
            set_include_path($cwd . $ipath . PATH_SEPARATOR .
                             $cwd . $ipath . "/PowerTLA". PATH_SEPARATOR .
                             get_include_path());
            break;
        }
        $cwd = dirname($cwd);
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
