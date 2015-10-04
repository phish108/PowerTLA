<?php

abstract class VLEHandler extends Logger
{
    protected $lmstype;

    protected $guestuserid;

    protected $tlapath;
    protected $tlahost;
    protected $baseurl;

    protected $privileges;

    /**
     * internal data store for LMS Interfaces.
     */
    private $handler;

    public function __construct($lmspath, $lmstype)
    {
        $this->lmstype = $lmstype;
        $this->tlapath = $lmspath;

        $this->tlahost = $_SERVER["SERVER_PROTOCOL"] . "://" . $_SERVER["SERVER_NAME"];

        $this->baseurl = $this->tlahost . "/" . $this->tlapath . "/restservice";

        $this->handler = new stdClass();
        $this->initLMS($lmspath);
    }

    abstract protected function initLMS($tlapath);

    public function idpURI()
    {
        return $this->baseurl . "/identity/profile.php/";
    }

    public function xapiURI()
    {
        return $this->baseurl . "/xapi/lrs.php/";
    }

    public function courseURI()
    {
        return $this->baseurl . "/content/course.php/";
    }

    public function qtiURI()
    {
        return $this->baseurl . "/content/qti.php/";
    }

    public function rsdURI()
    {
        return $this->baseurl . "rsd.php";
    }

    public function getUserId()
    {
        return $this->getIdentityProvider()->getUserId();
    }

    public function getUserToken()
    {
        $retval = null;
        $userDetails = $this->getIdentityProvider()->getUserDetails();

        if ($userDetails)
        {
            $retval = $userDetails["id"];
        }

        return $retval;
    }

    public function getAgentProfile()
    {
        if (!$this->isGuestUser())
        {
            return $this->getUserToken();
        }
        return null;
    }

    /**
     * validates an agent object (as used by XAPI)
     */
    public function validateAgent($agent)
    {
        if(isset($agent) && gettype($agent) == "array")
        {
            $idp = $this->getIdentityProvider();

            if(array_key_exists("mbox", $agent))
            {
                $mail = array_pop(explode(":", $agent["mbox"]));
                return $idp->findUserByMail($mail);
            }

            if(array_key_exists("openid", $agent))
            {
                $aTmp = explode("/", $agent["openid"]);
                $token = array_pop($aTmp);

                return $idp->findUserByToken($token);
            }

            if(array_key_exists("account", $agent))
            {
                if(array_key_exists("name", $agent["account"]))
                {
                    return $idp->findUserByLogin($agent["account"]["name"]);
                }

                if(array_key_exists("homepage", $agent["account"]))
                {
                    return $idp->findUserByHomepage($agent["account"]["homepage"]);
                }
            }
        }

        return null;
    }

    public function checkAgentProfile($profile)
    {
        if (isset($profile) &&
            $profile["id"] == $this->getAgentProfile())
        {
            return true;
        }
        return FALSE;
    }

    public function isGuestUser()
    {
        return $this->getIdentityProvider()->isGuestUser();
    }

    public function isActiveUser()
    {
        return !$this->isGuestUser();
    }

    public function apiDefinition($tlapath)
    {
        $pm = $this->getPluginManager();
        return $pm->getAPI($tlapath);
    }

    /**
     * this function loads the actual privileges for the given system.
     *
     * This function expects two contexts: the object context and the user context
     * Both contexts hold exactly one id.
     *
     * The user context hods a user id. checkPrivileges() should check if the
     * present user id is allowed to access information for the provided user id.
     *
     * The object context holds a content/activity object id. checkPrivileges()
     * must set the object privileges depending the the assigned privileges of the
     * active user.
     *
     * if both user and object context are present, then checkPrivileges() must
     * check whether the provided user context has access to the provided object
     * context. In this configuration, the function must check if the active user
     * is undefined OR the guest user. If this is the case, the function MUST NOT
     * retrieve the actual privileges, but an object that has all properties
     * set to false.
     *
     * If the context is empty the function must assume the user context of the
     * active user.
     *
     * This function MUST return the completed privilege object.
     */
    abstract protected function checkPrivileges($context, $privileges);

    /**
     * creates the default privilege object
     */
    protected function initPrivileges($bCheckActiveUser = true)
    {
        $privileges = new stdClass();

        // two types of privileges
        $privileges->personal = new stdClass();
        $privileges->context  = new stdClass();

        $privileges->personal->readObject        = false;
        $privileges->personal->writeObject       = false;
        $privileges->personal->readProfile       = true;
        $privileges->personal->writeProfile      = false;
        $privileges->personal->readActionStream  = false;
        $privileges->personal->writeActionStream = false;

        $privileges->context->readObject         = false;
        $privileges->context->writeObject        = false;
        $privileges->context->readProfile        = false;
        $privileges->context->writeProfile       = false;
        $privileges->context->readActionStream   = false;
        $privileges->context->writeActionStream  = false;

        if ($bCheckActiveUser &&
            !$this->isGuestUser())
        {
            // Guest users are only allowed to read certain contents,
            // but are always forbidden to write the activity stream.
            //
            // Ordinary users are allowed to read and write to their
            // activity stream.
            $this->log("no guest privilege");
            $privileges->personal->readActionStream  = true;
            $privileges->personal->writeActionStream = true;
            $privileges->personal->writeProfile      = true;
        }
        return $privileges;
    }

    /**
     * loads the privileges of a user for a given context.
     * The context is an array of the kind of a query parameter list.
     *
     * The function accepts a context, which will be either an object
     * context or a user context
     *
     * PowerTLA supports differnt privilege types.
     *
     * 1. personal privileges
     * 2. context privileges
     *
     * each privilege set has the same set of attributes, which define the
     * range of data that can get exposed to the active user. The ACL should
     * always check the personal privileges before the context privileges.
     *
     * NOTE: Most LMS build on a role system rather than a user-based AC.
     * Within this scope, the user privileges are typically in the form of
     * "can do" and "can do for others". The former refers to personal
     * privileges, while the latter refers to context definitions.
     *
     * PowerTLA is ignorant about the user roles and just accepts the
     * different role specific privileges and permissions. In order to
     * provide a consistent API to the privileges, it abstracts from the
     * various forms.
     *
     * * readObject: can read a given object
     * * writeObject: can write a given object
     * * readProfile: can read a user profile
     * * writeProfile: can change a user profile
     * * readActionStream: can read the LRS action stream
     * * writeActionStream: can write the LRS action stream
     */
    public function getPrivileges($context)
    {
        // enable privilege caching
        if (!isset($this->privileges))
        {
            // return global privileges
            $this->privileges = $this->checkPrivileges($context,
                                                       $this->initPrivileges());
        }

        return $this->privileges;
    }

    /**
     * Plugin provides the core Interface to the LMS, such as Access Control
     * and Mode handling
     */
    public function getPluginManager()
    {
        if (!property_exists($this->handler, "plugin"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/PluginConfig.class.php';
            $this->handler->plugin = new PluginConfig($this);
        }

        return $this->handler->plugin;
    }

    /**
     * The session validator is the prime interface to user authorisation.
     */
    public function getSessionValidator()
    {
        if (!property_exists($this->handler, "validator"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/SessionValidator.class.php';
            $this->handler->validator = new SessionValidator($this);
        }

        return $this->handler->validator;
    }

    public function getCourseBroker()
    {
        if (!property_exists($this->handler, "courseBroker"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/CourseBroker.class.php';
            $this->handler->courseBroker = new CourseBroker($this);
        }

        return $this->handler->courseBroker;
    }

    public function getQTIPoolBroker()
    {
        if (!property_exists($this->handler, "qtiBroker"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/QTIPoolBroker.class.php';
            $this->handler->qtiBroker = new QTIPoolBroker($this);
        }

        return $this->handler->qtiBroker;
    }

    public function getLRS()
    {
        if (!property_exists($this->handler, "lrs"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/LRSManager.class.php';
            $this->handler->lrs = new LRSManager($this);
        }

        return $this->handler->lrs;
    }

    public function getIdentityProvider()
    {
        if (!property_exists($this->handler, "idp"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/IdentityProvider.class.php';
            $this->handler->idp = new IdentityProvider($this);
        }

        return $this->handler->idp;
    }

    public function getClientProvider()
    {
        if (!property_exists($this->handler, "client"))
        {
            require_once 'PowerTLA/' . $this->lmstype . '/ClientProvider.class.php';
            $this->handler->client = new ClientProvider($this);
        }

        return $this->handler->client;
    }

    public function setGuestUser($username)
    {
        if (isset($username) && !empty($username))
        {
            $this->guestuserid = $username;
        }
    }
}

?>
