<?php
require_once("../../include/findVLE.php");


$vleapi = findIliasInstance();
if ($vleapi) {
    $service = new CourseService();

    $service->setVLE($vleapi);

    // CORS should be OK for the testing.
    // In production code we need to have additional access control for CORS Sites
    $service->allowCORS();
    $service->addCORSHost('*', array('GET', 'POST', 'PUT', 'DELETE'));

    $service->run();
}
?>
