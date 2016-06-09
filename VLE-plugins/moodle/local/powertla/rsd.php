<?php
/**
 * This code is part of PowerTLA and Licensed under GNU Affero License 3.0
 *
 * @author: Christian Glahn
 */

// This script will be invoked by the RSD2 plugin.

/**
 * set up PowerTLA
 */
$TLAConfig = parse_ini_file(__DIR__ . "/powertla.ini", true);

// set the include paths so we can host the common include files outside the LMS folder
set_include_path($TLAConfig["PowerTLA"]["include_path"] . PATH_SEPARATOR .
                 $TLAConfig["RESTling"]["include_path"] . PATH_SEPARATOR .
                 get_include_path());

// ensure that PHP has the local timezone instantiated
date_default_timezone_set($TLAConfig["PowerTLA"]["TLA_TIMEZONE"]);

// setup constants
define("TLA_TOKENTYPE", $TLAConfig["PowerTLA"]["TLA_TOKENTYPE"]);

// Init Autoloaders for RESTling and PowerTLA Classes
include_once('contrib/Restling.auto.php');
include_once('PowerTLA.auto.php');

/**
 * prepare the powerTLA service link
 */

$tpath  = explode(DIRECTORY_SEPARATOR, __DIR__);
$pathname  = array_pop($tpath);

$engineRoot =  $pathname . "/rest.php/";

$pathname = array_pop($tpath);

if ($pathname == "local") {
    // we are installed in local ... this is enforced on moodle
    $engineRoot = $pathname . "/" . $engineRoot;
}

$owd = getcwd();

chdir($TLAConfig["PowerTLA"]["include_path"] . "/Service");

// Evaluate all Services for the 4 TLA components
foreach (array("LRS", "Content", "Identity", "Competences") as $serviceType) {

    error_log($serviceType);

    $enginepath = $engineRoot . $serviceType;
    /**
     * fetch API information for each service
     *
     * Services are always named as 'class.<SERVICENAME>Service.php'.
     * All other files are ignored
     */
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceType)) as $file) {
        // include API files.
        $filename = $file->getFilename();

        list( $pre, $classname, $suffix) = explode('.', $filename);

        if (!empty($classname) &&
            $suffix == "php") {

            $fname = "PowerTLA\\Service\\$serviceType\\$classname::apiDefinition";
//            error_log('fname= ' . $fname );
//            error_log('dname= ' . $file->getPathname());

            try{
                require_once("./" . $file->getPathname());


                // Note: because of call_user_func we cannot pass the apis array as reference :(
                $tapis = call_user_func($fname, $apis, $enginepath);

                foreach ($tapis as $k => $v) {
                    $apis[$k] = $v;
                }
            }
            catch(Execption $e) {
                // ignore
                error_log($e->getMessage());
            }
        }
    }
}

chdir($owd);

<?php
/**
 * This code is part of PowerTLA and Licensed under GNU Affero License 3.0
 *
 * @author: Christian Glahn
 */

// This script will be invoked by the RSD2 plugin.

/**
 * set up PowerTLA
 */
$TLAConfig = parse_ini_file(__DIR__ . "/powertla.ini", true);

// set the include paths so we can host the common include files outside the LMS folder
set_include_path($TLAConfig["PowerTLA"]["include_path"] . PATH_SEPARATOR .
                 $TLAConfig["RESTling"]["include_path"] . PATH_SEPARATOR .
                 get_include_path());

// ensure that PHP has the local timezone instantiated
date_default_timezone_set($TLAConfig["PowerTLA"]["TLA_TIMEZONE"]);

// setup constants
define("TLA_TOKENTYPE", $TLAConfig["PowerTLA"]["TLA_TOKENTYPE"]);

// Init Autoloaders for RESTling and PowerTLA Classes
include_once('contrib/Restling.auto.php');
include_once('PowerTLA.auto.php');

/**
 * prepare the powerTLA service link
 */

$tpath  = explode(DIRECTORY_SEPARATOR, __DIR__);
$pathname  = array_pop($tpath);

$engineRoot =  $pathname . "/rest.php/";

$pathname = array_pop($tpath);

if ($pathname == "local") {
    // we are installed in local ... this is enforced on moodle
    $engineRoot = $pathname . "/" . $engineRoot;
}

$owd = getcwd();

chdir($TLAConfig["PowerTLA"]["include_path"] . "/Service");

// Evaluate all Services for the 4 TLA components
foreach (array("LRS", "Content", "Identity", "Competences") as $serviceType) {

    error_log($serviceType);

    $enginepath = $engineRoot . $serviceType;
    /**
     * fetch API information for each service
     *
     * Services are always named as 'class.<SERVICENAME>Service.php'.
     * All other files are ignored
     */
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serviceType)) as $file) {
        // include API files.
        $filename = $file->getFilename();

        list( $pre, $classname, $suffix) = explode('.', $filename);

        if (!empty($classname) &&
            $suffix == "php") {

            $fname = "PowerTLA\\Service\\$serviceType\\$classname::apiDefinition";
//            error_log('fname= ' . $fname );
//            error_log('dname= ' . $file->getPathname());

            try{
                require_once("./" . $file->getPathname());


                // Note: because of call_user_func we cannot pass the apis array as reference :(
                $tapis = call_user_func($fname, $apis, $enginepath);

                foreach ($tapis as $k => $v) {
                    $apis[$k] = $v;
                }
            }
            catch(Execption $e) {
                // ignore
                error_log($e->getMessage());
            }
        }
    }
}

chdir($owd);

?>