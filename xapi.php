<?php

require_once("include/ilServiceInit.php");  



require_once("include/class.XAPIService.php");


// TODO CHECK THE PLUGIN STATEMENTS
$plugins = array("oauth" => array("UIComponent", "uiroa", "OAuthREST"),
                 "xapi"  => array("UIComponent", "uixapi", "XAPIREST"));

$VLEAPI  = new ilServiceInit($plugins); 
$service = new XAPIService($VLEAPI);

$service->run();

?>