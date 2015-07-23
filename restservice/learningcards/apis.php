<?php
$localpath = "/learningcards";
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
        $cwd = explode('/', $scwd);

        $ipath = "/include";

        while (array_pop($cwd))
        {
            if (file_exists(implode('/', $cwd) . $ipath . "/findVLE.php"))
            {
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

        include_once("PowerTLA/tearupILIAS.php");
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

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.auth",
    "link" => $pathprefix . "/authentication.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.device",
    "link" => $pathprefix . "/registration.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.courses",
    "link" => $pathprefix . "/courses.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.questions",
    "link" => $pathprefix . "/questions.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.statistics",
    "link" => $pathprefix . "/statistics.php"
));

array_push($service["apis"], array(
    "name"   => "ch.isn.lms.tracking",
    "link" => $pathprefix . "/tracking.php"
));

$ap = explode("/", $pathprefix);
array_pop($ap);
$pathprefix = implode("/", $ap);
?>
