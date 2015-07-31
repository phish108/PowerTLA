<?php

class VLEHandler extends Logger
{
    protected $dbhandler;
    protected $user;

    protected $guestuserid;

    protected $tlapath;
    protected $tlahost;

    public function __construct($tlapath)
    {
        $this->tlapath = $tlapath;
        $this->tlahost = $_SERVER["SERVER_PROTOCOL"] . "://" . $_SERVER["SERVER_NAME"];
    }

    public function getUserId()
    {
        return $this->user;
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

    public function getDBHandler()
    {
        return $this->dbhandler;
    }



    protected function setDBHandler($dbh)
    {
        $this->dbhandler = $dbh;
    }

    public function isPluginActive($pname)
    {
        return false;
    }

    // active()  should return true after validateSession() and initiateSession() succeeded
    public function validateSession($sessionid)
    {}

    public function initiateSession($credentials)
    {}

    public function active()
    {
        return false;
    }

    public function getCourseBroker()
    {
        return null;
    }

    public function getQTIPoolBroker()
    {
        return null;
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
