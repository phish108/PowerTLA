<?php

namespace PowerTLA;

class Security implements \RESTling\Interfaces\Security\Model {
    private $noGuest = true;

    public function allowGuest() {
        $this->noGuest = false;
    }

    public function forbidGuest() {
        $this->noGuest = true;
    }

    public function noGuest() {
        return $this->noGuest;
    }
}

?>
