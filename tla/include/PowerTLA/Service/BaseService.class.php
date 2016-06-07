<?php

namespace PowerTLA\Service;

class BaseService extends \RESTling\Service {

    /**
     * @property $VLE
     *
     */
    public    $VLE;

    public static function apiDefinition($apis, $prefix, $link, $name)
    {
        // trim all leading slashes
        $prefix = preg_replace("/^\/+/", "", $prefix);

        $apis[$name] = array(
            "apiLink" => $prefix . "/" . $link,
            "transport" => array("REST")
        );

        return $apis;
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

            $validator = $vle->getSessionValidator();
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
     * @method void
     */
    protected function initializeRun()
    {
        $this->response_type = "json";
        // we do not know until this point which LMS we are dealing with
        // in order to workaround greedy systems we need to grab our data
        // as early as possible.
        // $this->loadData();

        if ($this->status == RESTling::OK)
        {
            // now the lms can work.
            // moodle might be confused that something snatched the data first.

            if (!$this->VLE)
            {
                $this->status = RESTling::UNINITIALIZED;
            }
            else if (!$this->VLE->getPluginManager()->isActive())
            {
                $this->log("PowerTLA has been deactivated!");
                // we need a different status for this case.
                $this->status = RESTling::UNINITIALIZED;
                // TODO: add additional information if in maintenace mode
            }
            else
            {
                parent::initializeRun();
            }
        }
    }
}

?>
