<?php

$services["auth.oauth"] = array(
    "id"   => "auth.oauth",
    "href" => "oauth.php",
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
