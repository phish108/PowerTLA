<?php
namespace PowerTLA\Interfaces;

interface Loader {
    public function findAndLoad($service, $apiIdentifier);
    public function loaded();
}
?>
