<?php

abstract class PluginBase extends Logger
{
    private $externalURL;

    abstract public function getEngine($tlapath);
    abstract public function getDisplayName();
    abstract public function getDefaultLanguage();

    /**
     * generates the basic system url for external requests.
     */
    protected function buildExternalSystemUrl($tlapath)
    {
        if (!isset($this->exteranalURL))
        {
            $reqpath = $_SERVER["REQUEST_URI"];

            // get rid of any query string garbage
            $reqpath = preg_replace('/\?.*$/',"", $reqpath);

            // get rid of the rsd section
            $reqpath = preg_replace('/\/[\w\d]+\.php$/',"", $reqpath);

            // strip the tla root
            $rcp = preg_replace('/\//', '\\/', $tlapath);
            $reqpath = preg_replace('/' . $rcp . '$/',"", $reqpath);

            $requrl = "http";
            $requrl .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
            $requrl .= $_SERVER["SERVER_NAME"];
            $requrl .= $reqpath;

            $this->externalURL = $requrl;
        }
        return $this->externalURL;
    }

    public function getAPI($tlapath)
    {
        $this->mark();
        $requrl = $this->buildExternalSystemUrl($tlapath);

        $retval = array(
            "engine" => $this->getEngine($tlapath),
            "language" => $this->getDefaultLanguage(),
            "tlaversion" => TLA_VERSION,
            "logolink" => $requrl . TLA_ICON,
            "name"     => $this->getDisplayName()
        );
        return $retval;
    }
}

?>
