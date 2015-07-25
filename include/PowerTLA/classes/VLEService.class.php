<?php

class VLEService extends RESTling {

    /**
     * @property $VLE
     *
     */
    public    $VLE;
    protected $dbh;

    protected $pluginList;

    public static function apiDefinition($prefix, $callprefix)
    {
        return null;
    }

    public function __construct()
    {
        parent::__construct();

        $this->pluginList = array(); // setthe the plugins to test
    }

    public function setVLE($vle)
    {
        if (isset($vle)) {
            $this->VLE = $vle;

            $validator = $vle->getAuthValidator();
            if (isset($validator))
            {
                $myheaders = getallheaders();

                if (array_key_exists("Authorization", $myheaders) &&
                    isset($myheaders["Authorization"]) &&
                    !empty($myheaders["Authorization"]))
                {
                    $authheader = $myheaders["Authorization"];
                    $aHeadElems = explode(' ', $authheader);

                    $validator->setTokenType($aHeadElems[0]);
                    $validator->setToken($aHeadElems[1]);
                }
                $this->addValidator($validator);
            }
        }
    }

    /**
     * @public
     * @method getAuthValidator()
     * @returns {OBJECT}
     *
     * The auth validator is used for the Authorization Headers.
     */
    public function getAuthValidator()
    {
        return null;
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
