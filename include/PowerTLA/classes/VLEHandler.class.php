<?php

class VLEHandler extends Logger
{
    protected $dbhandler;
    protected $user;
    protected $plugins;

    public function __construct($plugins)
    {
        if (!empty($plugins))
        {
            $this->plugins = $plugins;
        }
    }

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

    public function arePluginsActive()
    {
        if (empty($this->plugins))
        {
            return false;
        }
        
        foreach ( $this->plugings as $pName => $v )
        {
            if (!empty($pName) && !$this->isPluginActive($pName))
            {
                      return false;
            }
        }
        
        return true;
    }

    public function isPluginActive($pname)
    {
        return false;
    }
}

?>