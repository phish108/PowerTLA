<?php

class SystemHandler extends VLEHandler
{
    protected $iliasVersion;
    private $rbacAcl;  // ILIAS calls its access control RBAC

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

    protected function checkPrivileges($context, $privileges)
    {
        require_once 'Services/AccessControl/classes/class.ilRbacSystem.php';

        global $ilUser;
        $userid = $ilUser->getId();

        if (!isset($this->rbacAcl))
        {
            $this->rbacAcl = ilRbacSystem::getInstance();
        }

        $this->log(json_encode($context));
        $this->log(json_encode($privileges));

        $mode = 1; // 1 = this user
                   // 2 = other user
                   // 3 = object
                   // 4 = other user privs
        if (property_exists($context, 'object'))
        {
            $mode  = 3;
            $objid = $context->object;
        }
        if (property_exists($context, 'user'))
        {
            $mode += 1;
            $privileges  = $this->initPrivileges(false);
            $userid = $context->user;
        }

        if (!isset($privileges))
        {
            $privileges = $this->initPrivileges();
        }

        if ($mode >= 3)
        {

            // get read privileges
            $privileges->personal->readObject = $this->rbacAcl->checkAccessOfUser($userid,
                                                                                  'read',
                                                                                  $objid);
            $privileges->context->readObject = $privileges->personal->readObject;
            // get write privileges
            $privileges->personal->writeObject = $this->rbacAcl->checkAccessOfUser($userid,
                                                                                  'write',
                                                                                  $objid);
            $privileges->context->writeObject = $privileges->personal->writeObject;

            // get learning progress teacher's privileges
            $priv = 'read_learning_progress';
            $privileges->context->readActionStream = $this->rbacAcl->checkAccessOfUser($userid,
                                                                                       'read_learning_progress',
                                                                                       $objid);

            if (!$privileges->context->readActionStream)
            {
                // if the priv is missing NOW, we need to lookup the parent
                global $tree;
                $objid = $tree->getParentId($objid);
                $privileges->context->readActionStream = $this->rbacAcl->checkAccessOfUser($userid,
                                                                                           'read_learning_progress',
                                                                                           $objid);
            }

            if (!$privileges->context->readActionStream)
            {
                // if the user is lacking privileges now,
                // it is also necessary to verify if the current object
                // is an organisational unit because in that case there is a
                // different privilege.
                $privileges->context->readActionStream = $this->rbacAcl->checkAccessOfUser($userid,
                                                                                           'view_learning_progress',
                                                                                           $objid);
            }

        }

        if ($mode === 2 || $mode === 4)
        {
            // this is a different part of the ACL!

            // get learning progress reading privileges
            // get learning progress update privileges
        }

        return $privileges;
    }
}

?>
