<?php
namespace PowerTLA;

abstract class Plugin {
    static public function installApiFromFile($filename, $autoload = [], $cluster = "content", $classname = "") {
        list($api, $autoload, $cluster, $classname, $protocol) = self::prepareApiParameters($filename, $autoload, $cluster, $classname);
        static::installApi($api, $autoload, $cluster, $classname, $protocol);
    }

    abstract static public function installApi($api, $autoload, $cluster, $classname, $protocol);

    static public function setupApis($folder, $lms="", $nsPrefix=["PowerTLA","Model"]) {
        foreach (["content", "identity", 'lrs', 'orchestration'] as $cluster) {
            $prefix = "$folder/$cluster";
            if (!is_file($prefix)) {
                next; // skip
            }

            $files = scandir($prefix);
            foreach ($files as $file) {
                if (!is_dir("$prefix/$file") && strstr($file, ".json") === ".json") {
                    $config = new RESTling\Config\OpenApi();
                    $config->loadConfigFile("$prefix/$file");

                    $tags = self::tagClassName($config);

                    if (empty($lms)) {
                        $lms = [];
                    }
                    else {
                        $lms = [$lms];
                    }
                    $clsName = join("\\", array_merge($nsPrefix, $tags, $lms));

                    static::installApiFromFile("$prefix/$file", [], $cluster, $clsName);
                }
            }
        }
    }

    static protected function prepareApiParameters($filename, $autoload, $cluster, $classname) {
        if (empty($filename) || !file_exists($filename) || !is_file($filename)) {
            throw new Exception("No Filename provided");
        }

        if (empty($cluster)) {
            $cluster = "content";
        }

        if (strpos($cluster, '/') === false) {
            $fn =  explode(".", filename($filname));
            $cluster = join("/", [$cluster,$fn[0]]);
        }

        $config = new RESTling\Config\OpenApi();
        $config->loadConfigFile($filename);

        $clsList = [];
        if (empty($classname)) {
            // guess model classname from config and autoload
            $prefix = "";
            foreach ($autoload as $ns => $v) {
                $prefix = explode('\\', $ns);
                break;
            }

            $tags = self::tagClassName($config);

            $clsList = array_merge($prefix, $tags);
            $classname = join('\\', $clsList);
        }
        else {
            $clsList = explode("\\",$classname);
        }

        $protocol = "local." . join(".", $clsList);
        if ($info = $config->getInfo() && array_key_exists("x-protocol", $info)) {
            // we have an official protocol
            $protocol = info["x-protocol"];
        }

        $cfg = $config->export(); // export JSON

        return [$cfg, json_encode($autoload), $cluster, $classname, $protocol];
    }

    static protected function tagClassName($config) {
        $tags = $config->getTags(true);
        for ($i=0; $i < count($tags); $i++) {
            $tags[$i] = ucfirst(strtolower($tags[$i]));
        }
        return $tags;
    }
}
?>
