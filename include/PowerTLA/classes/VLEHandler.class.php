<?php

class VLEHandler extends Logger
{
    protected $dbhandler;
    protected $user;

    protected $guestuserid;

    public function __construct()
    {}

    public function getUserId()
    {
        return $this->user;
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

    public function getCourseBroker()
    {
        return null;
    }

    public function setGuestUser($username)
    {
        if (isset($username) && !empty($username))
        {
            $this->log("set guestuser to ". $username);
            $this->guestuserid = $username;
        }
    }
}

?>
