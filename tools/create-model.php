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
        fwrite($fh, "\n");
        if (!empty($summary)) {
            fwrite($fh, "    /**\n");
            $asum = explode("\n", $summary);
            $indent = '     * ';
            foreach ($asum as $line) {
                fwrite($fh, $indent . $line . "\n");
            }
            fwrite($fh, "$indent/\n");
        }

        fwrite($fh, "    public function $mName(\$input) {\n");
        if ($parent) {
            fwrite($fh, "        parent::$mName(\$input);\n");
        }
        fwrite($fh, "\n    }\n");
    }
}

?>
