<?php
$cwd = dirname(__FILE__);

$ipath = "/include";

while ($cwd != "/")
{
    if (file_exists($cwd . $ipath . "/findVLE.php"))
    {
        set_include_path($cwd . $ipath . PATH_SEPARATOR .
                         $cwd . $ipath . "/PowerTLA". PATH_SEPARATOR .
                         get_include_path());
        break;
    }
    $cwd = dirname($cwd);
}

$service = new QTIService();

$service->run();

?>
