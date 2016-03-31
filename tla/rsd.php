<?php
// powertla rsd descriptor class.

class tla {
    public static function describe($homepagelink = "", $enginelink = "") {
        // init the path prefix
        $pathprefix = "tla/";
        $apis = array();

        // set the include paths to our home
        $cwd = dirname(__FILE__);

        set_include_path($cwd . '/include' . PATH_SEPARATOR .
                             $cwd . '/include' . "/PowerTLA" . PATH_SEPARATOR .
                             get_include_path());

        // init auto loaders
        require_once("findVLE.php");

        // if homepagelink and engineline are empty, we need to use absolute paths
        if (empty($homepagelink) && empty($enginelink)) {
            $reqpath = dirname($_SERVER["REQUEST_URI"]);

            // strip the tla root
            $rcp = preg_replace('/\//', '\\/', $pathprefix);
            $reqpath = preg_replace('/' . $rcp . '.*$/',"", $reqpath);

            $requrl = "http";
            $requrl .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
            $requrl .= $_SERVER["SERVER_NAME"];
            $requrl .= $reqpath;

            // ensure a trailing slash
            if (!preq_match("/\/$/", $requrl)) {
                $requrl .= "/";
            }

            $pathprefix = $requrl . $pathprefix;
        }

        // traverse through the PowerTLA service directories.
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("restservice")) as $file) {
            if ($file->getFilename() == "apis.php") {
                $enginepath = $pathprefix . $file->getPath();

                // execute the api file within our own scope
                include("./" . $file->getPathname());
            }
        }

        return $apis;
    }
}

?>
