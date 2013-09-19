<?php

// Include the auto loading hooks for RESTling and our own classes
include('$_SERVER["DOCUMENT_ROOT"]./include/RESTling/contrib/Restling.auto.php');
include('$_SERVER["DOCUMENT_ROOT"]./include/PowerTLA/PowerTLA.auto.php');

require_once('$_SERVER["DOCUMENT_ROOT"]./include/PowerTLA/ilias/IliasServiceInit.php');     // the LMS functions
require_once('$_SERVER["DOCUMENT_ROOT"].include/classes/PowerTLA/AuthService.class.php'); // The service logic

$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"));

// TODO: wrapper that decides which LMS initialization has to be used. 
$VLEAPI = new IliasServiceInit($plugins); 
$service = new AuthService($VLEAPI);

$service->run();

?>