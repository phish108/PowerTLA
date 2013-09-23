<?php
set_include_path(".." . PATH_SEPARATOR . get_include_path());

// Include the auto loading hooks for RESTling and our own classes
include('include/RESTling/contrib/Restling.auto.php');
include('include/PowerTLA/PowerTLA.auto.php');


require_once('include/PowerTLA/Ilias/IliasServiceInit.class.php');     // the LMS functions
// require_once('include/PowerTLA/AuthService.class.php'); // The service logic

$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"));

// TODO: wrapper that decides which LMS initialization has to be used. 
$VLEAPI = new IliasServiceInit($plugins); 


$service = new AuthService($VLEAPI);

$service->run();

?>