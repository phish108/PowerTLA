<?php

namespace PowerTLA\Model\LRS\XAPI;

use \PowerTLA\Model\Identity\Webfinger\Ilias as Webfinger;

class Ilias extends \PowerTLA\Model\LRS\XAPI
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
