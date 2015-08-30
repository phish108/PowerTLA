<?php
$localpath = "identity";
if (!isset($pathprefix))
{
    $pathprefix = ".";
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

    require_once("findVLE.php");

    $service = getVLEInstanceInformation($rwd);
}

array_push($service["apis"], ProfileService::apiDefinition($pathprefix));
array_push($service["apis"], ClientService::apiDefinition($pathprefix));

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);
?>
