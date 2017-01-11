<?php
namespace PowerTLA\Loader\Database;

class Moodle extends \PowerTLA\Loader\Database {
    protected function findConfiguration($apiId) {
        global $DB;

        $retval  = [];
        if ($spec = $DB->get_record($this->relation,
                                    array("cluster" => $apiId))) {
            $retval["api"] = $spec->api;
            $retval["classname"] = $spec->classname;
            $retval["autoload"] = $spec->autoload;
            $retval["cluster"] = $spec->cluster;
            $retval["protocol"] = $spec->protocol;
        }

        return $retval;
    }
}
?>
