<?php
$localpath = "learningcards";
if (!isset($pathprefix))
{
    $pathprefix = ".";
}
else {
    $pathprefix .= "/". $localpath;
}

if (!isset($service))
{
    // find ilias instance to load the metadata
    $scwd = getcwd();
    $cwd = explode('/', $scwd);
    $ipath = "/include";
    $xpath = array();

    if (!file_exists("include/findVLE.php"))
    {
        while ($p = array_pop($cwd))
        {
            array_unshift($xpath, $p);
            if (file_exists(implode('/', $cwd) . $ipath . "/findVLE.php"))
            {
                set_include_path(implode('/', $cwd). $ipath . PATH_SEPARATOR .
                                 implode('/', $cwd). $ipath . "/PowerTLA". PATH_SEPARATOR .
                                 get_include_path());
                break;
            }
        }
    }

    require_once("findVLE.php");

    $service = getVLEInstanceInformation(implode('/', $xpath));
}

$pathprefix = preg_replace("/^\/+/", "", $pathprefix);

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.auth",
    "link" => $pathprefix . "/authentication.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.device",
    "link" => $pathprefix . "/registration.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.courses",
    "link" => $pathprefix . "/courses.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.questions",
    "link" => $pathprefix . "/questions.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.statistics",
    "link" => $pathprefix . "/statistics.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.tracking",
    "link" => $pathprefix . "/tracking.php"
));

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);
?>
