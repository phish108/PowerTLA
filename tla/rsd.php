<?php
// powertla rsd descriptor class.

// init the path prefix
$pathprefix = "tla";

// set the include paths to our home
$cwd = dirname(__FILE__);

// init auto loaders
set_include_path($cwd .DIRECTORY_SEPARATOR. 'include' . PATH_SEPARATOR .
                 $cwd .DIRECTORY_SEPARATOR.'include' .DIRECTORY_SEPARATOR. "PowerTLA" .     PATH_SEPARATOR .
                 get_include_path());

require_once("findVLE.php");

// if homepagelink and engineline are empty, we need to use absolute paths
if (empty($homePageLink) && empty($engineLink)) {
    $reqpath = dirname($_SERVER["REQUEST_URI"]);

    // strip the tla root
    $reqpath = preg_replace('/'. $pathprefix . '\/.*$/',"", $reqpath);

    $requrl = "http";
    $requrl .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
    $requrl .= $_SERVER["SERVER_NAME"];
    $requrl .= $reqpath;

    // ensure a trailing slash
    if (!preg_match("/\/$/", $requrl)) {
        $requrl .= "/";
    }

    $pathprefix = $requrl . $pathprefix;
}

// enter our own directory
$owd = getcwd();
chdir($cwd);

// traverse through the PowerTLA service directories.
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("restservice")) as $file) {
    // include API files.
    if ($file->getFilename() == "apis.php") {

        // PowerTLA Api Providers should use the $enginepath to prefix their apiLinks
        $enginepath = $pathprefix . DIRECTORY_SEPARATOR . $file->getPath();

        // execute the api file within our own scope
        include("./" . $file->getPathname());
    }
}

// return to original directory
chdir($owd);

?>
