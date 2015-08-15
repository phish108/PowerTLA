<?php

class IliasHandler extends VLEHandler
{
    protected $pluginAdmin;
    protected $plugins;

    protected $iliasVersion;
    protected $validator;

    public static function init($tp)
    {
        $retval = FALSE;
        include_once("include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
        if (!empty($aVersion))
        {
            $vstring = $aVersion[0] . '.' . $aVersion[1];

            $strVersionInit = 'PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

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
                        $retval = TRUE;
                        break;
                    case '4.3':
                        ilRESTInitialisation::initIlias(); // why oh why?!?
                        $retval = TRUE;
                        break;
                    case '4.4':
                    case '5.0':
                        ilRESTInitialisation::initILIAS(); // fake OOP again,
                                                           // but now all CAPS?
                        $retval = TRUE;
                        break;
                    default:
                        break;
                }
            }
        }
        return $retval;
    }

    public static function apiDefinition($tp, $cp)
    {
        $retval = null;

        if (self::init($tp))
        {
            global $ilClientIniFile;

            $servername = $ilClientIniFile->readVariable('client',   'description');
            $lang =       $ilClientIniFile->readVariable('language', 'default');

            $aPath = explode('/', $cp);
            $lPath = explode('/', $tp);
            array_pop($lPath);          // remove include directory
            if (!empty($aPath)) {
                while (count($lPath)) {
                    array_unshift($aPath, array_pop($lPath));
                }
            }

            $reqpath = $_SERVER["REQUEST_URI"];

            // get rid of any query string garbage
            $reqpath = preg_replace('/\?.*$/',"", $reqpath);

            // get rid of the rsd section
            $reqpath = preg_replace('/\/[\w\d]+\.php$/',"", $reqpath);

            // find the external server root
            $aReq = explode("/", $reqpath);

            $aPath = array_reverse($aPath);
            foreach ($aPath as $a)
            {
                if (!empty($a))
                {
                    $x = array_pop($aReq);
                    if ($a != $x)
                    {
                        array_push($aReq, $x);
                    }
                }
            }
            $aPath = array_reverse($aPath);

            $requrl = "http";
            $requrl .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
            $requrl .= $_SERVER["SERVER_NAME"];
            $requrl .= implode('/',$aReq);

            $retval = array(
                "engine" => array(
                    "version" => ILIAS_VERSION_NUMERIC,
                    "type"=> "ILIAS",
                    "link"=> $requrl, // official link
                    "servicelink" => $requrl . "/". implode("/", $aPath)
                ),
                "language" => $lang,
                "tlaversion" => "0.6",
                "logolink" => $requrl . TLA_ICON,
                "name"     => $servername
            );
        }
        return $retval;
    }

    public function __construct($tp)
    {
        global $optNoRedirect;
        $optNoRedirect = true;

        // assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.
        if (self::init($tp))
        {
            parent::__construct($tp);

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

    public function getAuthValidator()
    {
        if (!isset($this->validator))
        {
            require_once 'PowerTLA/Ilias/SessionValidator.class.php';
            $this->validator = new SessionValidator();
        }
        return $this->validator;
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

    public function getClientProvider()
    {
        require_once 'PowerTLA/Ilias/ClientProvider.class.php';
        return new ClientProvider();
    }

    public function getIdentityProvider()
    {
        require_once 'PowerTLA/Ilias/IdentityProvider.class.php';
        return new IdentityProvider($this->guestuserid);
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
