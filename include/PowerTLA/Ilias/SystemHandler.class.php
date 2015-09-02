<?php

class SystemHandler extends VLEHandler
{
    protected $pluginAdmin;
    protected $plugins;

    protected $iliasVersion;
    protected $validator;

    protected function initLMS($tp)
    {
        include_once($tp . "/include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
        if (!empty($aVersion))
        {
            $vstring = $aVersion[0] . '.' . $aVersion[1];

            $strVersionInit = 'tla/include/PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

            if (file_exists($tp.'/'.$strVersionInit) )
            {
                // $this->log("ilias file exists");
                require_once($strVersionInit);

                switch ($vstring)
                {
                    case '4.2':
                        $ilInit = new ilRESTInitialisation();
                        $GLOBALS['ilInit'] = $ilInit;
                        $ilInit->initILIAS("rest");
                        break;
                    case '4.3':
                        ilRESTInitialisation::initIlias(); // why oh why?!?
                        break;
                    case '4.4':
                    case '5.0':
                        ilRESTInitialisation::initILIAS(); // fake OOP again,
                                                           // but now all CAPS?
                        break;
                    default:
                        $this->fatal("Unsupported Ilias Version " . $vstring);
                        break;
                }
            }
        }
    }

    public function __construct($tp)
    {
        global $optNoRedirect;
        $optNoRedirect = true;

        // assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.
        if (self::init($tp))
        {
            parent::__construct($tp, 'Ilias');

            $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
            $this->iliasVersion  = $aVersion[0] . '.' . $aVersion[1];
            // now we can initialize the system internals
            // We should always avoid to fall back into Ilias' GLOBAL mode
            global $ilUser, $ilDB;

            $this->dbhandler    = $ilDB;
            $this->user         = $ilUser;

            //$this->pluginAdmin  = $GLOBALS['ilPluginAdmin'];
            //$this->log("ilias init done");
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

    public function setGuestUser($username)
    {
        parent::setGuestUser($username);
        global $ilUser;

        if (!empty($this->guestuserid) &&
            (!$ilUser->getId() || $ilUser->getLogin() == "anonymous"))
        {
            $guid = $ilUser->getUserIdByLogin($this->guestuserid);

            $ilUser->setId($guid);
            $ilUser->read();
        }
    }

    public function getUserId()
    {
        global $ilUser;
        return $ilUser->getId();
    }


    public function isGuestUser()
    {
        global $ilUser;

        if ($ilUser->getId() &&
            $ilUser->getLogin() != "anonymous" &&
            !isset($this->guestuser) ||
            (!empty($this->guestuser) &&
            $ilUser->getLogin() != $this->guestuser))
        {
            return FALSE;
        }

        return TRUE;
    }

    public function getXAPIStatement()
    {
        require_once 'PowerTLA/Ilias/VleXAPIStatement.class.php';
        return new VleXAPIStatement();
    }

    public function getXAPIDocument()
    {
        require_once 'PowerTLA/Ilias/VleXAPIDocument.class.php';
        return new VleXAPIDocument();
    }
}

?>
