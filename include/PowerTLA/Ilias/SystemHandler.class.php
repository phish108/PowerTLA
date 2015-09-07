<?php

class SystemHandler extends VLEHandler
{
    protected $iliasVersion;
    protected $privileges;

    public function __construct($tp)
    {
        global $optNoRedirect;
        $optNoRedirect = true;

        // assume that PowerTLA lives in the same include path.
        // We require a configuration variable that informs us about the LMS include path.

        parent::__construct($tp, 'Ilias');
    }

    protected function initLMS($tp)
    {
        include_once($tp . "/include/inc.ilias_version.php");

        $aVersion   = explode('.', ILIAS_VERSION_NUMERIC);
        if (!empty($aVersion))
        {
            $vstring = $aVersion[0] . '.' . $aVersion[1];

            $strVersionInit = 'tla/include/PowerTLA/Ilias/ilRESTInitialisation.' . $vstring . '.php';

            $this->iliasVersion  = $aVersion[0] . '.' . $aVersion[1];
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

    public function getVersion()
    {
        return $this->iliasVersion;
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

    /**
     * loads the privileges of a user for a given context.
     * The context is an array of the kind of a query parameter list.
     *
     * We support 8 privileges
     * - readObjectSelf: Read data on the given object that is directly available
     *                   to the user.
     * - readContextSelf: Read the own data that is available in the broader context
     * - readContext: allow to access all data in a given context (typically managing)
     * - writeObjectSelf: Write own data on the object
     *
     * if a user has access to read an given objectId/activityId
     *     readObjectSelf will be TRUE (default TRUE)
     * if a user can manage a given objectId/activityId
     *     readObjectSelf and readObject will be TRUE (default FALSE)
     * if a user can provide input to an objectId/activityId
     *     writeObjectSelf will be TRUE (default FALSE, if not guest, TRUE)
     * if a user can access the framing context
     *     readContextSelf will be TRUE  (default: TRUE)
     * if a user can manage the framing context
     *     readContext and writeContext will be TRUE (default FALSE)
     */
    public function getPrivileges($context)
    {
        // enable caching privileges
        if (!isset($this->privileges))
        {
            // return global privileges
            $privileges = new stdClass();

            $privileges->readObjectSelf   = true;
            $privileges->readObject       = false;
            $privileges->readContextSelf  = true;
            $privileges->readContext      = false;
            $privileges->writeObjectSelf  = false;
            $privileges->writeObject      = false;
            $privileges->writeContextSelf = false;
            $privileges->writeContext     = false;

            if (!$this->isGuestUser())
            {
                $privileges->writeObjectSelf = true;
            }
            $this->privileges = $privileges;
        }

        return $this->privileges;
    }
}

?>
