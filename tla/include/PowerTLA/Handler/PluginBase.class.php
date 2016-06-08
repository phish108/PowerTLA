<?php

namespace PowerTLA\Handler;
use PowerTLA\Handler\BaseHandler;

abstract class PluginBase extends BaseHandler
{
    private $externalURL;

    abstract public function getEngine($tlapath);
    abstract public function getDisplayName();
    abstract public function getDefaultLanguage();

    public function isActive() {
        return true;
    }
}

?>
