<?php


header('content-type: application/json');

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

chdir($cwd);
include("rsd.php");

$service['apis'] = tla::describe($service['homePageLink'], $service["engineLink"]);

echo(json_encode($service));

?>
