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
            $reqpath = dirname($_SERVER["REQUEST_URI"]);

            // strip the tla root
            $rcp = preg_replace('/\//', '\\/', $tlapath);
            $reqpath = preg_replace('/' . $rcp . '\/.*$/',"", $reqpath);

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
            "homePageLink" => $requrl,
            "engineLink" => $tlapath,
            // "language" => $this->getDefaultLanguage(),
            "homePageIcon" => $requrl . TLA_ICON,
            "engineName"     => $this->getDisplayName()
        );
        return $retval;
    }
}

?>
