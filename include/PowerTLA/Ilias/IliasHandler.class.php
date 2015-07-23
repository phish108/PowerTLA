<?php

class IliasHandler extends VLEHandler
{
    protected $pluginAdmin;
    protected $plugins;

    protected $tlapath;
    protected $baseurl;

    protected $iliasVersion;

    public function __construct($tp)
    {
        global $optNoRedirect;
        $optNoRedirect = true;

    	// assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.

        include_once("include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);

        if (!empty($aVersion)) {
            $vstring = $aVersion[0] . '.' . $aVersion[1];

            $this->log("ilias version is  " . $vstring);

            $this->iliasVersion = $vstring;

            $strVersionInit = 'PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

            $this->log("strVersionInit is ".$strVersionInit);

            if (file_exists($tp . '/' . $strVersionInit) )
            {
                // $this->log("ilias file exists");
                require_once($strVersionInit);


                $this->log('init ' . $vstring);
                // initialize Ilias
                // unfortunately they change the initialization routine completely between releases
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

                // now we can initialize the system internals
                // We should always avoid to fall back into Ilias' GLOBAL mode
                global $ilUser;

                $this->tlapath = $tp;
                $this->dbhandler    = $GLOBALS['ilDB'];
                $this->user         = $ilUser;
                $this->setBasePath();

                //$this->pluginAdmin  = $GLOBALS['ilPluginAdmin'];
                //$this->log("ilias init done");
            }
            else
            {
                 $this->log("ilias file does not exist");
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
        return $this->user->getId();
    }

    private function setBasePath()
    {
        return;
        $tp = explode('/', $this->tlapath);
        array_pop($tp);
        array_pop($tp);
        $tp = implode('/', $tp);
        // strip include suffix
        $pos = strpos(ILIAS_HTTP_PATH, $tp);
        // now strip everything from that position to the end
        $this->baseurl = substr(ILIAS_HTTP_PATH, 0, $pos);
    }

    public function getBaseURL()
    {
        return $this->baseurl;
    }

    public function getCourseBroker()
    {
        require_once 'PowerTLA/Ilias/CourseBroker.class.php';
        return new CourseBroker($this->iliasVersion);
    }

    public function getQTIPoolBroker()
    {
        require_once 'PowerTLA/Ilias/QTIPoolBroker.class.php';
        return new QTIPoolBroker($this->iliasVersion);
    }
}

?>
