<?php

$localpath = "/restservice";

if (!isset($pathprefix))
{
    $pathprefix = "";
}
else {
    $pathprefix .= $localpath;
}

if ( !isset($service))
{
    // find ilias instance to load the metadata
    $scwd = getcwd();

    if (file_exists("include/findVLE.php"))
    {
        set_include_path($scwd . "/include" . PATH_SEPARATOR .
                         $scwd . "/include/PowerTLA" . PATH_SEPARATOR .
                         get_include_path());
    }
    else
    {
        error_log("find the detector");
        $cwd = explode('/', $scwd);

        $ipath = "/include";

        while (array_pop($cwd))
        {
            if (file_exists(implode('/', $cwd) . $ipath . "/findVLE.php"))
            {
                error_log("found detector");
                set_include_path(implode('/', $cwd). $ipath . PATH_SEPARATOR .
                                 implode('/', $cwd). $ipath . "/PowerTLA". PATH_SEPARATOR .
                                 get_include_path());
                break;
            }
        }
    }

    require_once("findVLE.php");

    $type = "AnyLMS";

    $handlerCls = findIliasInstance();
    if (!empty($handlerCls)) {
        $type = "ILIAS";

        require_once("PowerTLA/tearupILIAS.php");
        global $ilClientIniFile;

        $servername = $ilClientIniFile->readVariable('client', 'description');
    }


    $reqpath = "http";
    $reqpath .= !empty($_SERVER["HTTPS"]) ? "s://" : "://";
    $reqpath .= $_SERVER["SERVER_NAME"];
    $reqpath .= $_SERVER["REQUEST_URI"];

    // get rid of any query string
    $reqpath = preg_replace('/\?.*$/',"", $reqpath);

    // get rid of the rsd section
    $reqpath = preg_replace('/\/rsd\.php$/',"", $reqpath);

    // FIXME FIND THE CORRECT LMS AND METADATA
    $service = array(
        "engine" => array(
            "version" => "0.5",
            "type"=> $type,
            "link"=> $reqpath // official link
        ),
        "apis"   => array(),
        "language" => "en",
        "tlaversion" => "MBC.1.0",
        "logolink" => $reqpath . "/icon.png",
        "name"     => $servername
    );
}

//array_push($service["apis"], array(
//    "name"   => "gov.adlnet.xapi.lrs",
//    "link" => $pathprefix . "/lrs.php"
//));
//
//array_push($service["apis"], array(
//    "name"   => "powertla.content.imsqti.json",
//    "link" =>  $pathprefix . "/content/qti.php"
//));
//
//array_push($service["apis"], array(
//    "name"   => "auth.oauth",
//    "link" =>  $pathprefix . "/profiles/oauth.php"
//));

include_once("learningcards/apis.php");

?>