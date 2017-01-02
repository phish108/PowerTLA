<?php

namespace PowerTLA\Security;

class Moodle implements \RESTling\Interfaces\Security\Model {

    public function validateKey($key, $input) {
        throw new \RESTling\Exception\Security\SharedKeyValidationUnsupported();
        // this function is called for all normal settings
    }

    public function validateToken($token, $input) {
        throw new \RESTling\Exception\Security\TokenValidationUnsupported();
        // this function is called in a Authorization header is present.
    }
}

?>
