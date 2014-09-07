<?php

class IliasHandler extends VLEHandler
{
    protected $pluginAdmin;
    protected $plugins;

    protected $tlapath;

    public function __construct($tp)
    {
    	// assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.

        include_once("include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);

        if (!empty($aVersion)) {
            $vstring = $aVersion[0] . '.' . $aVersion[1];

            $this->log("ilias version is  " . $vstring);

            $strVersionInit = 'PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

            // $this->log("strVersionInit is ".$strVersionInit);

            if (file_exists($tp . $strVersionInit) )
            {
                // $this->log("ilias file exists");
                require_once($strVersionInit);

                // initialize Ilias
                // unfortunately they change the initialization routine completely between releases
                switch ($vstring)
                {
                    case '4.2':
                       $this->log('init ' . $vstring);
                       $ilInit = new ilRESTInitialisation();
                       $GLOBALS['ilInit'] = $ilInit;
                       $ilInit->initILIAS();
                       break;
                    case '4.3':
                        $this->log('init ' . $vstring);

                        ilRESTInitialisation::initIlias(); // why oh why?!?
                        break;
                    default:
                        return;
                        break;
                }

                // now we can initialize the system internals
                // We should always avoid to fall back into Ilias' GLOBAL mode
                $this->dbhandler    = $GLOBALS['ilDB'];
                $this->user         = $GLOBALS['ilUser'];

                //$this->pluginAdmin  = $GLOBALS['ilPluginAdmin'];
                //$this->log("ilias init done");
            }
            // else
            // {
            //     $this->log("ilias file does not exist");
            // }
        }
    }


    public function isPluginActive($pName)
    {
        if (!empty($pName) && array_key_exists($pName, $this->plugins))
        {
            return $this->pluginAdmin->isActive(IL_COMP_SERVICE,
                                                $this->plugins[$pName][0],
                                                $this->plugins[$pName][1],
                                                $this->plugins[$pName][2]);
        }
    }

    public function isActiveUser()
    {
        if($this->user->getLogin())
        {
            return true;
        }
        return false;
    }

    public function getActiveUserId()
    {
        return $this->user->getId();
    }
}

?>
