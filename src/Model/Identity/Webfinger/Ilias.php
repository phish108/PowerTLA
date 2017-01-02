<?php

namespace PowerTLA\Model\Identity\Webfinger;

class Ilias extends \PowerTLA\Model\Identity\Webfinger
{
    protected function getSystemId() {
        // FIXME use the client name
        return $_SERVER["SERVER_NAME"];
    }
}

?>
