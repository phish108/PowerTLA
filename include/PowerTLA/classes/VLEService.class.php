<?php

class VLEService extends RESTling {

    /**
     * @property $VLE
     *
     */
    public    $VLE;
    protected $dbh;

    protected $pluginList;

    public function __construct()
    {
        parent::__construct();

        $this->pluginList = array(); // setthe the plugins to test
    }

    public function setVLE($vle)
    {
        if ($vle) {
            $this->VLE = $vle;
        }
    }

    /**
     * @method void
     */
    protected function initializeRun()
    {
        $this->response_type = "json";

        if (!$this->dbh && !$this->VLE) {
            $this->status = RESTling::UNINITIALIZED;
        }
        else {
            parent::initializeRun();
        }
    }
}

?>
