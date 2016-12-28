<?php
namespace PowerTLA\Loader;

class File implements PowerTLA\Interfaces\Loader {
    private $baseDir = "";
    private $cfgDir = "apis";
    private $cfgLoaded = false;

    public function __construct($baseDir) {
        if (empty($baseDir) || !file_exists($baseDir)) {
            throw new \PowerTLA\Exception\InvalidBaseDirectory();
        }
        $this->baseDir = $baseDir;
    }

    public function setConfigurationDirectory($cfgDir) {
        if (empty($cfg) || !file_exists($this->baseDir . DIRECTORY_SEPARATOR . $cfgDir)) {
            throw new \PowerTLA\Exception\InvalidConfigurationDirectory();
        }
        $this->cfgDir = $cfgDir;
    }

    public function findAndLoad($service, $apiIdentifier) {
        if (empty($this->baseDir)) {
            throw new \PowerTLA\Exception\MissingBaseDirectory();
        }
        if (!in_array($apiIdentifier[0], ["identity", "lrs", "content", "orchestration"])) {
            throw new \PowerTLA\Exception\InvalidTlaCluster();
        }

        $cfgDir = [$this->baseDir, $this->cfgDir];

        $path = array_merge($baseDir, $apiIdentifier);
        $apiFile = join(DIRECTORY_SEPARATOR, $path);

        if (!(file_exists($apiFile) || file_exists("$apiFile.json") || file_exists("$apiFile.yaml"))) {
            throw new \PowerTLA\Exception\MissingApiSpecificationFile();
        }

        if (file_exists($cfgFile)) {
            $service->loadConfigFile($cfgFile);
        }
        elseif (file_exists("$cfgFile.yaml")) {
            $service->loadConfigFile("$cfgFile.yaml");
        }
        else {
            $service->loadConfigFile("$cfgFile.json");
        }
        $this->cfgLoaded = true;
    }

    public function loaded() {
        return $this->cfgLoaded;
    }
}
?>
