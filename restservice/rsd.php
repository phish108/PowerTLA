<?php


include_once("apis.php");

//$services["powertla.profiles.imslip.json"] = array(
//    "id"   => "powertla.profiles.imslip.json",
//    "href" =>  $pathprefix . "profiles/lip.php",
//    "version" => "0.0.1"
//);

header('content-type: application/json');
echo(json_encode($service));

?>
