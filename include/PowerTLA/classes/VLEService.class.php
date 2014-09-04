<?php

class VLEService extends RESTling {

    /**
     * @property $VLE
     *
     */
    protected $VLE;
    protected $dbh;

    protected $pluginList;

    public function __construct()
    {
        parent::__construct();

        $this->pluginList = array(); // setthe the plugins to test
    }

    public function setVLE($vle) {
        if ($vle) {
            $this->VLE = $vle;
            $this->dbh = $vle->getDBHandler();
        }
    }

    public function addPluginID($pluginid)
    {
        if (!empty(plugin))
        {
            array_push($this->pluginList, $pluginid);
        }
    }

    private function checkPlugins()
    {
        if ($this->status == RESTling::OK)
        {
            foreach ($this->pluginList as $p)
            {
                if (!empty($p) && !$this->VLE->isPluginActive($p)) {
                    $this->status = RESTling::UNINITIALIZED;
                    break;
                }
            }
        }
    }

    /**
     * @method void
     */
    protected function initializeRun() {
        $this->response_type = "json";

        if (!$this->dbh && !$this->VLE) {
            $this->status = RESTling::UNINITIALIZED;
        }
        else {
            parent::initializeRun();
            $this->checkPlugins();
        }
    }
}

?>
