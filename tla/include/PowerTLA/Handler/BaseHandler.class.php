<?php

namespace PowerTLA\Handler;

class BaseHandler extends \RESTling\Logger {
    protected $VLE;

    public function __construct($system)
    {
        $this->VLE = $system;
    }
}

?>