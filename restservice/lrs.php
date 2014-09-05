<?php

set_include_path("tla/include/" . PATH_SEPARATOR . "tla/include/PowerTLA/". PATH_SEPARATOR . get_include_path());

chdir("../..");

// Include the auto loading hooks for RESTling and our own classes
require_once('RESTling/contrib/Restling.auto.php');
require_once('PowerTLA/PowerTLA.auto.php');

require_once('PowerTLA/Ilias/IliasHandler.class.php');

// TODO: CHECK THE PLUGIN STATEMENTS
//$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"),
//                 "xapi"  => array("UIComponent", "uixapi", "XAPIREST"));


// TODO: wrapper that decides which LMS initialization has to be used.
$VLEAPI  = new IliasHandler();

$service = new XAPIService();

$service->setVLE($VLEAPI);

// CORS should be OK for the testing.
// In production code we need to have additional access control for CORS Sites
$service->allowCORS();
$service->addCORShost('*', array('GET', 'POST', 'PUT'));

$service->run();

?>
