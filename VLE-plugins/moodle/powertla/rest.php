<?php
/**
 * This code is part of PowerTLA and Licensed under GNU Affero License 3.0
 *
 * @author: Christian Glahn
 */

/**
 * Code Logic
 *
 * Part 1: Setup the LMS PowerTLA environment
 * Part 2: Service Discovery
 * Part 3: Service Launch
 */

/** *****************************************************************
 * Part 1: Setup the LMS PowerTLA environment
 *
 * In this part we setup the core environment and include paths
 */

define("TLA_LMS", "Moodle");

// note: if the powertle.ini is missing this script will fail with
// an 500 error code (as it should).

// TODO: The plugin UI should generate the ini file
$TLAConfig = parse_ini_file("powertla.ini", true);

// set the include paths so we can host the common include files outside the LMS folder
set_include_path($TLAConfig["PowerTLA"]["include_path"] . PATH_SEPARATOR .
                 $TLAConfig["RESTling"]["include_path"] . PATH_SEPARATOR .
                 get_include_path());

// ensure that PHP has the local timezone instantiated
date_default_timezone_set($TLAConfig["PowerTLA"]["TLA_TIMEZONE"]);

// setup constants
define("TLA_TOKENTYPE", $TLAConfig["PowerTLA"]["TLA_TOKENTYPE"]);

// Init Autoloaders for RESTling and PowerTLA Classes
include_once('RESTling/contrib/Restling.auto.php');
include_once('PowerTLA.auto.php');

// preload the VLE System handler
require_once('PowerTLA/' . TLA_LMS . '/SystemHandler.class.php');

/**
 * @function detectLMS()
 *
 * legacy function for bootstrapping PowerTLA to the LMS handler
 */
function detectLMS() {
    $vle  = new SystemHandler();
    $vle->setGuestUser($TLAConfig["PowerTLA"]["TLA_GUESTUSER"]);
    return $vle;
}

/** *****************************************************************
 * Part 2: Service Discovery
 *
 * Identify the service to launch on the grounds of the request URL.
 *
 * During this phase the PATH_INFO variable is stripped, so the
 * service can work as it would have been launched via a dedicated
 * script.
 *
 *
 */
if(array_key_exists("PATH_INFO", $_SERVER) &&
   !empty($_SERVER["PATH_INFO"])) {

    $pi = explode("/", $_SERVER["PATH_INFO"]);
    $e = array_shift($pi);
    $serviceType = array_shift($pi);
    $serviceName = array_shift($pi);

    array_unshift($e, $pi);
    $_SERVER["PATH_INFO"] = implode("/", $pi);

    if (isset($serviceName) &&
        !empty($serviceName) &&
        in_array($serviceType, array("LRS", "Content", "Identity", "Competences")) &&
        isset($serviceName) &&
        !empty($serviceName)) {

        $serviceName = ucfirst(strtolower($serviceName));
        $serviceName .= "Service";

        // preload the service class
        try {
            require_once($serviceType . "/class." . $serviceName . ".php");
        }
        catch(Exception $e) {
            // Service class does not exist
            $service = new ErrorService("loading", $e->getMessage());
        }
    }
}

/** *****************************************************************
 * Part 3: Service Launch
 *
 * Instanciate the service class and launch the service
 *
 * Note: if something goes seriously wrong until this point we will launch our
 * Error Service.
 */
if (!isset($serviceName)&& empty($serviceName)) {
    $service = new ErrorService("invalid call", "Missing Service");
}

// try to instantiate the service class
try {
    $service = new $serviceName();
}
catch(Exception $e) {
    $service = new ErrorService("instantiation", $e->getMessage());
}

// run the service
$service->run();

?>
