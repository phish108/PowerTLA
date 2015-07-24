<?php
$scwd = getcwd();
$cwd = explode('/', $scwd);
$ipath = "/include";

while (count($cwd))
{
    if (file_exists(implode('/', $cwd) . $ipath . "/findVLE.php"))
    {
        set_include_path(implode('/', $cwd). $ipath . PATH_SEPARATOR .
                         implode('/', $cwd). $ipath . "/PowerTLA". PATH_SEPARATOR .
                         get_include_path());
        break;
    }
    array_pop($cwd);
}

require_once("findVLE.php");

$vleapi = detectLMS();
if ($vleapi) {
    $service = new QTIService();

    $service->setVLE($vleapi);

    // CORS should be OK for the testing.
    // In production code we need to have additional access control for CORS Sites
    $service->allowCORS();
    $service->addCORSHost('*', array('GET', 'POST', 'PUT', 'DELETE'));

    $service->run();
}

?>
