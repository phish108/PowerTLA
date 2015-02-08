<?php

$localpath = "/restservice";

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

    // get rid of any query string
    $reqpath = preg_replace('/\?.*$/',"", $reqpath);

    // get rid of the rsd section
    $reqpath = preg_replace('/\/rsd\.php$/',"", $reqpath);

    $service = array(
        "engine" => array(
            "version" => "0.5",
            "type"=> "ILIAS",
            "link"=> $reqpath // official link
        ),
        "apis"   => array(),
        "language" => "en",
        "tlaversion" => "MBC.1.0",
        "logolink" => $reqpath . "/icon.png",
        "name"     => "TEST SERVER"
    );
}

//array_push($service["apis"], array(
//    "name"   => "gov.adlnet.xapi.lrs",
//    "link" => $pathprefix . "/lrs.php"
//));
//
//array_push($service["apis"], array(
//    "name"   => "powertla.content.imsqti.json",
//    "link" =>  $pathprefix . "/content/qti.php"
//));
//
//array_push($service["apis"], array(
//    "name"   => "auth.oauth",
//    "link" =>  $pathprefix . "/profiles/oauth.php"
//));

include_once("learningcards/apis.php");

?>