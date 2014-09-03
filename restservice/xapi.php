<?php

set_include_path(".." . PATH_SEPARATOR . "../include/PowerTLA/". PATH_SEPARATOR . get_include_path());

// Include the auto loading hooks for RESTling and our own classes
include('../include/RESTling/contrib/Restling.auto.php');
include('../include/PowerTLA/PowerTLA.auto.php');

// require_once('include/PowerTLA/Ilias/IliasHandler.class.php');

// TODO: CHECK THE PLUGIN STATEMENTS
$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"),
                 "xapi"  => array("UIComponent", "uixapi", "XAPIREST"));


// TODO: wrapper that decides which LMS initialization has to be used.
//$VLEAPI  = new IliasHandler($plugins);

$service = new XAPIService();

$service->run();

?>
