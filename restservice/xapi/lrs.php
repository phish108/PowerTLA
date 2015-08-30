<?php
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


$vleapi = detectLMS();
if ($vleapi) {
    $service = new XAPIService();

    $service->setVLE($vleapi);

    // CORS should be OK for the testing.
    // In production code we need to have additional access control for CORS Sites
    $service->allowCORS();
    $service->addCORSHost('*', array('GET', 'POST', 'PUT'));

    $service->run();
}

?>
