<?php

if (!isset($pathprefix))
{
    $pathprefix = "";
}

$services = array();

$services["gov.adlnet.xapi.lrs"] = array(
    "id"   => "gov.adlnet.xapi.lrs",
    "href" => $pathprefix . "lrs.php",
    "version" => "1.0.0"
);

$services["powertla.content.imsqti.json"] = array(
    "id"   => "powertla.content.imsqti.json",
    "href" =>  $pathprefix . "content/qti.php",
    "version" => "0.0.1"
);

$services["auth.oauth"] = array(
    "id"   => "auth.oauth",
    "href" =>  $pathprefix . "profiles/oauth.php",
    "version" => "1.0"
);

//$services["powertla.profiles.imslip.json"] = array(
//    "id"   => "powertla.profiles.imslip.json",
//    "href" =>  $pathprefix . "profiles/lip.php",
//    "version" => "0.0.1"
//);

header('content-type: application/json');
echo(json_encode($services));
?>
