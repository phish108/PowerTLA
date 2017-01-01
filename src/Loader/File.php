<?php
namespace PowerTLA\Loader;

class File implements PowerTLA\Interfaces\Loader {
    private $baseDir = "";
    private $cfgDir = "apis";
    private $cfgLoaded = false;

    private $autoloader;
    protected $nsMap;
    protected $tagModelPostfix;

    /**
 	 * constructor.
 	 *
     * @param composer autoloader object $autoloader
 	 * @param path $baseDir - baseDir for loading
     * @param array $namespaceMap - contains a single key value pair of the autoloader
 	 * @return void
	 */
	public function __construct($autoLoader, $baseDir, $nsMap = [], $postfix = []) {
        if (empty($baseDir) || !file_exists($baseDir)) {
            throw new \PowerTLA\Exception\InvalidBaseDirectory();
        }

        $this->nsMap = $nsMap;
        $this->autoloader = $autoLoader;
        $this->tagModelPostfix = $postfix;

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

        $apiConfig = new \RESTling\Config\OpenApi();

        if (file_exists($cfgFile)) {
            $apiConfig->loadConfigFile($cfgFile);
        }
        elseif (file_exists("$cfgFile.yaml")) {
            $apiConfig->loadConfigFile("$cfgFile.yaml");
        }
        else {
            $apiConfig->loadConfigFile("$cfgFile.json");
        }

        $tags = $apiConfig->getTags(true);
        $k = [];

        if (!empty($this->nsMap) && is_array($this->nsMap)) {
            $k = array_keys($this->nsMap);

            if (!empty($this->nsMap[$k[0]])) {
                $this->autoloader->setPsr4($k[0], $this->nsMap[$k[0]]);
            }
            $k = explode("\\", trim($k[0], "\\"));
        }

        if (!($this->postfix && is_array($this->postfix))) {
            $this->postfix = [];
        }

        $apiConfig = $apiConfig->setTagModel(array_merge($k, $tags, $this->postfix));

        $this->cfgLoaded = true;
        $service->setApiConfig($apiConfig);
    }

    public function loaded() {
        return $this->cfgLoaded;
    }
}
?>
