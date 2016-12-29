<?php

namespace PowerTLA\Service;

class Moodle extends \PowerTLA\Service {
    private $lmsRoot;

    protected function loadTagModel($taglist) {
        // add moodle to the end of the tag list that identifies the model.
        $taglist[] = "Moodle";
        parent::loadTagModel($taglist);
    }

    private function findVLE() {
        $cwd = __DIR__;
        $lmsPath;

        do {
            if (file_exists("$cwd/lib/moodlelib.php")) {
                $lmsPath = $cwd;
            }
            $cwd = dirname($cwd);
        } while (empty($lmsPath) && $cwd !== "/");

        if (empty($lmsPath)) {
            throw new \PowerTLA\Exception\MissingLearningEnvironment();
        }
        $this->lmsRoot = $lmsPath;
    }

    protected function verifyModel() {
        $this->findVLE();

        // Combined use of AJAX and Webseriveces creates conflicts!
        // The idea is that MC and the web ui use the same API
        define('AJAX_SCRIPT', true);
        // However, as soon as WS_Server is set, browser sessions will not be
        // evaluated. Don't activate for the time being.
        // define('WS_SERVER', true);

        // we have to run token evaluation independently

        require($this->lmsRoot . '/config.php');
        // tons of black magic is happening now

        // Now all moodle internals are set and we are ready to roll.
        parent::verifyModel();
    }
}
