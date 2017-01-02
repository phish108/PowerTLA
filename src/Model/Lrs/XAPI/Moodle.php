<?php

namespace PowerTLA\Model\LRS\XAPI;

use \PowerTLA\Model\Identity\Webfinger\Moodle as Webfinger;

class Moodle extends \PowerTLA\Model\LRS\XAPI
{
    private $webfingerModel;
    
    protected function getWebfingerModel() {
        if (!$this->webfingerModel) {
            $this->webfingerModel = new Webfinger();
        }
        return $this->webfingerModel;
    }
}

?>
