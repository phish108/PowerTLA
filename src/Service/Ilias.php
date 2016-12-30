<?php

namespace PowerTLA\Service;

class Ilias extends \PowerTLA\Service {
    private $lmsRoot;
    private $lmsVersion;

    public function __construct() {
        parent::__construct();

        // connect moodle's security model
        $this->setSecurityModel(new PowerTLA\Security\Ilias());
    }

    protected function loadTagModel($taglist) {
        // if the requested module is not a platform specific module
        if ($taglist[0] != "Ilias" && $taglist[1] != "PoweTLA") {
            // add Ilias to the end of the tag list that identifies the model.
            $taglist[] = "Ilias";
        }
        parent::loadTagModel($taglist);
    }

    private function findVLE() {
        $cwd = __DIR__;
        $lmsPath;

        do {
            if (file_exists("$cwd/include/inc.ilias_version.php")) {
                $lmsPath = $cwd;
            }
            $cwd = dirname($cwd);
        } while (empty($lmsPath) && $cwd !== "/");

        if (empty($lmsPath)) {
            throw new \PowerTLA\Exception\MissingLearningEnvironment();
        }

        $this->lmsRoot = $lmsPath;

        include_once("$lmsPath/include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
        if (empty($aVersion)) {
            throw new \PowerTLA\Exception\UnsupportedLearningEnvironment();
        }

        $vstring = $aVersion[0] . '_' . $aVersion[1];
        $classname = "\\PowerTLA\\Service\\Ilias\\Init_$vstring";
        if (!class_exists($classname, true)) {
            throw new \PowerTLA\Exception\UnsupportedLearningEnvironment();
        }

        // unlike moodle, Ilias uses the include path variable
        set_include_path($lmsPath . PATH_SEPARATOR .
                         get_include_path());

        $this->lmsVersion = $vstring;

        require_once $classname;
    }

    protected function verifyModel() {
        $this->findVLE();

        switch ($this->lmsVersion)
        {
            case '4_2':
                $ilInit = new $classname();
                $GLOBALS['ilInit'] = $ilInit;
                $ilInit->initILIAS("rest");
                break;
            case '4_3':
                $classname::initIlias(); // why oh why?!?
                break;
            case '4_4':
            case '5_0':
                $classname::initILIAS(); // fake OOP again,
                break;
            default:
                throw new \PowerTLA\Exception\UnsupportedLearningEnvironment();
                break;
        }
        parent::verifyModel();
    }
}
