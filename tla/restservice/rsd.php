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


$homepagelink = $service["homePageLink"];
$enginelink   = $service["engineLink"];

// chdir($cwd);
$apis = array();
include($cwd . "/rsd.php");

$service['apis'] = $apis;

echo(json_encode($service));

?>
