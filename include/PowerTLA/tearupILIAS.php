<?php

date_default_timezone_set("Europe/Zurich");

include_once("include/inc.ilias_version.php");

$aVersion   = explode('.', ILIAS_VERSION_NUMERIC);

if (!empty($aVersion)) {
    $vstring = $aVersion[0] . '.' . $aVersion[1];
    $strVersionInit = 'include/PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

    if (file_exists($strVersionInit) )
    {
        // $this->log("ilias file exists");
        require_once($strVersionInit);
        switch ($vstring)
        {
            case '4.2':
                $ilInit = new ilRESTInitialisation();
                $GLOBALS['ilInit'] = $ilInit;
                $ilInit->initILIAS();
                break;
            case '4.3':
                ilRESTInitialisation::initIlias(); // why oh why?!?
                break;
            case '4.4':
                ilRESTInitialisation::initILIAS(); // fake OOP again.
                break;
            default:
                return;
                break;
        }
        require_once 'Services/User/classes/class.ilObjUser.php';
    }
}

?>
