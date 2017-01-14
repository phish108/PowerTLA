<?php

namespace PowerTLA\Security;

class Ilias extends \PowerTLA\Security {
    public function validateKey($key, $input) {
        throw new \RESTling\Exception\Security\SharedKeyValidationUnsupported();
    }

    public function validateToken($token, $input) {
        throw new \RESTling\Exception\Security\TokenValidationUnsupported();
    }
}

?>
