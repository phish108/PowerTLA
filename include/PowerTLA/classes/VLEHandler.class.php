<?php

class VLEHandler extends Logger
{
    protected $dbhandler;
    protected $user;

    public function __construct()
    {}

    public function getUser()
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
}

?>
