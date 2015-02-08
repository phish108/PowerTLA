<?php
$localpath = "/learningcards";
if (!isset($pathprefix))
{
    $pathprefix = "";
}
else {
    $pathprefix .= $localpath;
}

if ( !isset($service))
{
    $reqpath = "http";
    $reqpath .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
    $reqpath .= $_SERVER["SERVER_NAME"];
    $reqpath .= $_SERVER["REQUEST_URI"];

    // get rid of the rsd section
    $reqpath = preg_replace('/\/rsd\.php$/',"", $reqpath);

    $service = array(
        "engine" => array(
            "version" => "0.5",
            "type"=> "ILIAS",
            "link"=> $reqpath // official service link
        ),
        "apis"   => array(),
        "language" => "en",
        "tlaversion" => "MBC.1.0",
        "logolink" => $reqpath . "/icon.png" // should point to the correct logo URL, possibly from ILIAS!
    );
}

$service["apis"]["ch.isn.lms.auth"] = array(
    "name"   => "ch.isn.lms.auth",
    "link" => $pathprefix . "/authentication.php"
);

$service["apis"]["ch.isn.lms.device"] = array(
    "name"   => "ch.isn.lms.device",
    "link" => $pathprefix . "/registration.php"
);

$service["apis"]["ch.isn.lms.courses"] = array(
    "name"   => "ch.isn.lms.courses",
    "link" => $pathprefix . "/courses.php"
);

$service["apis"]["ch.isn.lms.questions"] = array(
    "name"   => "ch.isn.lms.questions",
    "link" => $pathprefix . "/questions.php"
);

$service["apis"]["ch.isn.lms.statistics"] = array(
    "name"   => "ch.isn.lms.statistics",
    "link" => $pathprefix . "/statistics.php"
);

$service["apis"]["ch.isn.lms.tracking"] = array(
    "name"   => "ch.isn.lms.tracking",
    "link" => $pathprefix . "/tracking.php"
);
?>