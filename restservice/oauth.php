<?php

// Include the auto loading hooks for RESTling and our own classes
include('include/RESTling/contrib/Restling.auto.php');
include('include/PowerTLA.auto.php');

require_once("include/ilServiceInit.php");     // the LMS functions
require_once("include/class.AuthService.php"); // The service logic

$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"));

$VLEAPI = new ilServiceInit($plugins); 
$service = new AuthService($VLEAPI);

$service->run();

?>