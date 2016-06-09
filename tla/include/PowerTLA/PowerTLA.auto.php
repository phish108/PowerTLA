<?php

    /**
     * Autoloader for the Resting TLA subsystem for the PHP5 autoloader system.
     *
     * In order to use autoloading this file needs to be loaded during the
     * initialization of your root script.
     */

    define("TLA_VERSION", "0.9");

    // make the include path available to the entire system.
    global $powertlapath;

    // determine the relevant include path only once.
    $powertlapath = "";
    $prefixes = explode(PATH_SEPARATOR, get_include_path());

    foreach ( $prefixes as $p )
    {
        //    error_log($p);
        if (file_exists($p .'/PowerTLA.auto.php' ))
        {
            $powertlapath = $p;
            break;
        }
    }
    // error_log("auto: " . $powertlapath);

    spl_autoload_register(function ($class) {

        error_log("powertla-auto: " . $class);

        $class = ltrim($class, "\\");
        $parts = explode('\\', $class);

        $NSRoot = array_shift($parts);

        // error_log("powertla-auto: " . $NSRoot);

        if (isset($NSRoot) &&
            !empty($NSRoot)) {
            if($NSRoot == "PowerTLA") {

//                error_log("powertla-auto: " . $class);

                global $powertlapath;
                $path = $powertlapath . '/' . implode('/', $parts) . '.class.php';

//                error_log("auto: " . $path);

                if (file_exists($path))
                {
                    include_once $path;
//                    error_log("auto ok! " );
                }
            }
            // TODO: Provide a mechanism for VLE plugins
        }

    });
?>
