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
    $ipath = "/include";
    $xpath = array();

    if (!file_exists("include/findVLE.php"))
    {
        while ($p = array_pop($cwd))
        {
            array_unshift($xpath, $p);

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

    $service = getVLEInstanceInformation(implode('/', $xpath));
}

array_push($service["apis"], ProfileService::apiDefinition($pathprefix));
array_push($service["apis"], ClientService::apiDefinition($pathprefix));

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);
?>
