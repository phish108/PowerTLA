<?php
require_once("../../include/findeVLE.php");

$service = new QTIService();
// CORS should be OK for the testing.
// In production code we need to have additional access control for CORS Sites
$service->allowCORS();
$service->addCORSHost('*', array('GET', 'POST', 'PUT'));

$service->run();

?>
