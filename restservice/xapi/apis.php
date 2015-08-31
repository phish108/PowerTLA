<?php
$localpath = "xapi";
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
    $rwd = dirname($_SERVER["REQUEST_URI"]);
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

array_push($service["apis"], XAPIService::apiDefinition($pathprefix));
// array_push($service["apis"], XAPIFilterService::apiDefinition($pathprefix));

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);

?>
