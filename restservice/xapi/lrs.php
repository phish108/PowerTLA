<?php

require_once("../include/findVLE.php");
$tlapath = findIliasInstance();
if (!empty($tlapath)) {

    $service = new XAPIService();

    $VLEAPI  = new IliasHandler($tlapath);
    $service->setVLE($VLEAPI);

    // CORS should be OK for the testing.
    // In production code we need to have additional access control for CORS Sites
    $service->allowCORS();
    $service->addCORSHost('*', array('GET', 'POST', 'PUT'));

    $service->run();
}

?>
