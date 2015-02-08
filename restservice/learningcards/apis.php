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
        "name"     => "TEST SERVER",
        "logolink" => $reqpath . "/icon.png" // should point to the correct logo URL, possibly from ILIAS!
    );
}

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
?>