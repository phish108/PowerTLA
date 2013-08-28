<?php

class IliasServiceInit extends VLEHandler
{
    protected $pluginAdmin;
    protected $plugins;

    public function __construct($plugins)
    {

        parent::__construct($plugins);

        if (!empty($plugins))
        {
            // assume that PowerTLA lives in the same include path.
            // We require a configuration variable that informs us about the LMS include path.
            include_once("include/inc.ilias_version.php");

            $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
            $strVersionInit = 'restservice/include/ilRESTInitialization.' . $aVersion[0] . '.' . $aVersion[1] . '.php';

            if ( file_exists($strVersionInit) )
            {
                require_once($strVersionInit);

                require_once 'Services/Database/classes/class.ilDB.php';
                require_once 'Services/Component/classes/class.ilPluginAdmin.php';
                require_once 'Services/Component/classes/class.ilPlugin.php';

                // initialize Ilias
                $ilInit = new ilInitialisation();
                $GLOBALS['ilInit'] = $ilInit;
                $ilInit->initILIAS();

                // now we can initialize the system internals
                // We should always avoid to fall back into Ilias' GLOBAL mode
                $this->dbhandler    = $GLOBALS['ilDB'];
                $this->user         = $GLOBALS['ilUser'];
                $this->pluginAdmin  = $GLOBALS['ilPluginAdmin'];
            }
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
