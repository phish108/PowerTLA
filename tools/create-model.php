<?php

$baseDir = dirname(__DIR__);
require_once "$baseDir/vendor/autoload.php";

$SubModels = ["Moodle", "Ilias"];
$PathModelMap  = ["PowerTLA" => "$baseDir/src"];

$config = [];

// get service api
$configFile = end($argv);
// die($configFile);
$oaiConfig = new \RESTling\Config\OpenApi();
try {
    $oaiConfig->loadConfigFile($configFile);
}
catch (Exception $err) {
    die($err->getMessage());
}

$tags = $oaiConfig->getTags();
$tt = [];
foreach ($tags as $t) {
    $tt[] = $t["name"];
}
$tags = $tt;

$modelName = array_pop($tags);
$modelRoot = array_shift($tags);
$pModel = $oaiConfig->getPaths();

if (!in_array($modelRoot, array_keys($PathModelMap))) {
    die("Model Root is not matched");
}

$path = $PathModelMap[$modelRoot];

if (!(file_exists($path) && is_dir($path))) {
    die("Model Root is not a Directory");
}

foreach ($tags as $tag) {
    if (empty($tag)) {
        next;
    }

    $tag = ucfirst(strtolower($tag));

    if (!file_exists("$path/$tag")) {
        if (!mkdir("$path/$tag")) {
            die("Cannot create directory $path/$tag");
        }
    }
    $path .= "/$tag";
}

$tags  = $oaiConfig->getTags();
$tt = [];
foreach ($tags as $t) {
    $tt[] = $t["name"];
}
$tags = $tt;

$paths = $oaiConfig->getPaths();
// array_unshift($tags, "");

$fqModelName = "\\" . join("\\", $tags);
$fileName = "$path/$modelName.php";
createModel($fileName, $tags, $paths);

if (!empty($SubModels)) {
    if (!file_exists("$path/$modelName")) {
        mkdir("$path/$modelName");
    }

    foreach ($SubModels as $submodel) {
        $parentCls = '\\' . join('\\', $tags);
        $fileName = "$path/$modelName/$submodel.php";
        $ttags = array_merge($tags, [$submodel]);
        createModel($fileName, $ttags, $paths, $parentCls);
    }
}

function createModel($fname, $tags, $pathList, $parent=null) {
    global $oaiConfig;

    if (!file_exists($fname)) {
        $modelName   = array_pop($tags);
        $nameSpace   = join("\\", $tags);

        $parentClass = "\\RESTling\\Model";
        if (!empty($parent)) {
            $parentClass = $parent;
        }

        $fh = fopen($fname, "w");
        if ($fh) {
            fwrite($fh, "<?php\n\n");
            // TODO add copyright statement
            fwrite($fh, "namespace $nameSpace;\n\n");
            fwrite($fh, "class $modelName extends $parentClass\n");
            fwrite($fh, "{\n");
            if (!$parent) {
                foreach ($pathList as $path => $pathObj) {
                    $pathObj = $oaiConfig->expandObject($pathObj);

                    foreach ($pathObj as $op => $opObj) {
                        $opObj = $oaiConfig->expandObject($opObj);

                        if (in_array($op, ['summary', 'description', 'servers', 'parameters'])) {
                            next;
                        }

                        $funcName = selectFuncName($path, $op, $opObj);
                        if (array_key_exists("summary", $opObj)) {
                            $summary = $opObj["summary"];
                        }
                        createMethod($fh, $funcName, $parent, $summary);
                    }
                }
            }

            if (!$parent) {
                preprocessPaths($fh);
            }
            fwrite($fh, "}\n\n");
            fwrite($fh, "?>\n");
            fclose($fh);
        }
    }
}

function selectFuncName($path, $method, $operationObject) {
    if (array_key_exists("operationId", $operationObject)) {
        return $operationObject["operationId"];
    }

    $fName = strtolower($method);
    $aP    = explode("/", $path);

    foreach ($aP as $part) {
        $fName .= ucfirst(strtolower(trim($part, '{}')));
    }
    return "$fName.php";
}

function createMethod($fh, $mName, $parent, $summary) {
    if ($fh) {
        summaryComment($fh, $summary, 1);

        fwrite($fh, indent(1) . "public function $mName(\$input) {\n");
        if ($parent) {
            fwrite($fh, indent(2) . "parent::$mName(\$input);\n");
        }
        fwrite($fh, "\n" . indent(1) . "}\n");
    }
}

function preprocessPaths($fh) {
    global $oaiConfig;
    $pathMap = [];

    $paths = $oaiConfig->getPaths();

    $oPathMap = [];
    foreach ($paths as $path => $pathobj) {
        // translate the path into a regex, and filternames
        $apath  = explode("/", $path);
        $rpath  = [];
        $vnames = [];

        $pathobj = $oaiConfig->expandObject($pathobj);

        if (!empty($pathobj)) {
            foreach ($apath as $pe) {
                $aVarname = [];
                if (preg_match("/^\{(.+)\}$/", $pe, $aVarname)) {
                    $vnames[] = $aVarname[1];
                    $rpath[]  = '([^\/]+)';
                }
                else {
                    $rpath[] = $pe;
                }
            }

            $repath = '/^' . implode('\\/', $rpath) . '(?:\\/(.+))?$/';

            $oPathMap[] = [
                "pattern" => $repath,
                "pathitem" => $pathobj,
                "vars" => $vnames,
                "path" => $path
            ];
        }
    }

    usort($oPathMap, function ($a,$b){return strlen($b["pattern"]) - strlen($a["pattern"]);});

    $summary = "Returns the pathmap of the model.\n\n";
    $summary.= "This is automatically generated from the API specification. You can safely ignore this part.\n\n";
    $summary.= "Note: on API changes, this method may change too.";

    summaryComment($fh, $summary, 1);

    fwrite($fh, indent(1) . "final public function getPathMap() {\n");
    fwrite($fh, indent(2) . "return ");
    indentLines($fh, var_export($oPathMap, true), 3, true);
    fwrite($fh, ";\n");
    fwrite($fh, indent(1) . "}\n");
}

function indent($level) {
    $tab = "    ";
    return str_repeat($tab, $level);
}

function summaryComment($fh, $summary, $level) {
    if (!empty($summary)) {
        fwrite($fh, "\n" . indent($level) . "/**\n");
        $asum = explode("\n", $summary);
        $indent = ' *';
        foreach ($asum as $line) {
            fwrite($fh, indent($level) . $indent . " $line\n");
        }
        fwrite($fh, indent($level) . $indent ."/\n");
    }
}

function indentLines($fh, $string, $level, $notFrist = false) {
    $lines = explode("\n", $string);

    if ($notFrist) {
        $line = array_shift($lines);
        fwrite($fh, $line);
    }
    foreach ($lines as $line) {
        fwrite($fh, "\n" . indent($level) . $line);
    }
}

?>
