<?php

class VLEHandler extends Logger
{
    protected $lmstype;

    protected $guestuserid;

    protected $tlapath;
    protected $tlahost;
    protected $baseurl;

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

    protected function initLMS($tlapath) {}

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
                $token = array_pop(explode("/", $agent["openid"]));

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
