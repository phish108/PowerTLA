<?php
namespace PowerTLA\Interfaces;

interface Loader {
    public function findAndLoad($service);
    public function loaded();
}
?>
