<?php
$services = array();
$services["powertla.content.imsqti.json"] = array(
    "id"   => "powertla.content.imsqti.json"
    "href" => "qti.php"
);

header('content-type: application/json');
echo(json_encode($services));
?>
