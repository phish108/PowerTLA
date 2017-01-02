<?php
namespace PowerTLA\Loader\Database;

class Ilias extends \PowerTLA\Loader\Database {
    protected function findConfiguration($apiCluster) {
        global $ilDB;

        $retval  = [];
        $r = $ilDB->queryF("SELECT * FROM $this->relation WHERE cluster = %s",
                           array("text"),
                           array($apiCluster));

        if ($spec = $ilDB->fetchAssoc($r)) {
            foreach (["api", "postfix", "autoload", "cluster", "protocol"] as $k) {
                $retval[$k] = $spec[$k];
            }
        }

        return $retval;
    }
}
?>
