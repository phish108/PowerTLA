<?php

class VLEHandler extends Logger
{
    protected $lmstype;

    protected $guestuserid;

    protected $tlapath;
    protected $tlahost;

    /**
     * internal data store for LMS Interfaces.
     */
    private $handler;

    public function __construct($lmspath, $lmstype)
    {
        $this->lmstype = $lmstype;
        $this->tlapath = $lmspath;

        $this->tlahost = $_SERVER["SERVER_PROTOCOL"] . "://" . $_SERVER["SERVER_NAME"];

        $this->handler = new stdClass();

        $this->initLMS($lmspath);
    }

    protected function initLMS($tlapath) {}

    public function getUserId()
    {
        return -1;
    }

    public function getAgentProfile()
    {
        if (!$this->isGuestUser())
        {
            return $this->tlahost . "/" . $this->tlapath . "/restservice/profile/" . $this->getUserId();
        }
        return null;
    }

    public function checkAgentProfile($profile)
    {
        if ($profile == $this->getAgentProfile()) {
            return true;
        }
        return FALSE;
    }

    public function isGuestUser()
    {
        return TRUE;
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

    public function getXAPIStatement()
    {

        return new XAPIStatement();
    }

    public function getXAPIDocument()
    {
        return new XAPIDocument;
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
