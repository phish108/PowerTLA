<?php
require_once("findVLE.php");

class VLEService extends RESTling {

    /**
     * @property $VLE
     *
     */
    public    $VLE;

    public static function apiDefinition($prefix, $name, $link)
    {
        // trim all leading slashes
        $prefix = preg_replace("/^\/+/", "", $prefix);

        return array(
            "name"   => $name,
            "link" => $prefix . "/" . $link
        );
    }

    public function __construct()
    {
        parent::__construct();
        $this->setVLE();

        // CORS should be OK for the testing.
        // In production code we need to have additional access control
        // for CORS Sites

        // $service->allowCORS();
        // $service->addCORSHost('*', array('GET', 'POST', 'PUT'));

    }

    public function setVLE()
    {
        $vle = detectLMS();

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

        if (!$this->VLE) {
            $this->status = RESTling::UNINITIALIZED;
        }
        else {
            parent::initializeRun();
        }
    }
}

?>
