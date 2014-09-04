<?php

set_include_path("tla/include" . PATH_SEPARATOR . "tla/include/PowerTLA/". PATH_SEPARATOR . get_include_path());

chdir("../../..");


// Include the auto loading hooks for RESTling and our own classes
require_once('RESTling/contrib/Restling.auto.php');
require_once('PowerTLA/PowerTLA.auto.php');


$service = new QTIService();

$service->run();

?>
