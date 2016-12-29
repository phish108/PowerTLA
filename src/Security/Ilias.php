<?php

namespace PowerTLA\Security;

class Ilias implements \RESTling\Interfaces\Security\Model {
    public function validateKey($key, $in) {
        throw new \RESTling\Exception\Security\SharedKeyValidationUnsupported();
    }

    public function validateToken($token) {
        throw new \RESTling\Exception\Security\TokenValidationUnsupported();
    }
}

?>
