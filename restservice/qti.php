<?php

set_include_path(".." . PATH_SEPARATOR . "../include/PowerTLA/". PATH_SEPARATOR . get_include_path());

// Include the auto loading hooks for RESTling and our own classes
include('../include/RESTling/contrib/Restling.auto.php');
include('../include/PowerTLA/PowerTLA.auto.php');


$service = new QTIService();

$service->run();

?>
