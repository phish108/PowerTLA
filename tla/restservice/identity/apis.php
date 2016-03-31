<?php

$apis = ProfileService::apiDefinition($apis, $enginepath, "profile.php");
$apis = ClientService::apiDefinition($apis, $enginepath, "client.php");

?>
