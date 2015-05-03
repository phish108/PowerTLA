<?php

    /**
     * Autoloader for the Resting TLA subsystem for the PHP5 autoloader system.
     *
     * In order to use autoloading this file needs to be loaded during the
     * initialization of your root script.
     */

    // make the include path available to the entire system.
    global $powertlapath;

    // determine the relevant include path only once.
    $powertlapath = "";
    $prefixes = explode(PATH_SEPARATOR, get_include_path());
    foreach ( $prefixes as $p )
    {
        if (file_exists($p .'/PowerTLA.auto.php' ))
        {
            $powertlapath = $p;
            break;
        }
    }

    spl_autoload_register(function ($class) {
        global $powertlapath;

    	$path = $powertlapath . '/classes/' . $class . '.class.php';

        if (file_exists($path))
        {
            include_once $p . '/' . $path;
        }
    });
?>
