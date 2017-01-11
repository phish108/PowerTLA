<?php

namespace PowerTLA\Loader;

class Database implements PowerTLA\Interfaces\Loader {
    private $autoloader;
    private $nsMap;
    protected $relation;
    private $cfgLoaded = false;

    /**
 	 * constructor.
 	 *
     * @param composer autoloader object $autoloader
 	 * @param path $apiRelation - the database table that holds the api specs
 	 * @return \PowerTLA\Loader\Database
	 */
	public function __construct($autoLoader, $apiRelation) {
        $this->autoloader = $autoLoader;
        $this->relation = $apiRelation;
    }

    public function findAndLoad($service, $apiIdentifier) {

        if (!($apiIdentifier &&
              is_array($apiIdentifier)  &&
              count($apiIdentifier) == 2 &&
              in_array($apiIdentifier[0], ["identity", "lrs", "content", "orchestration"]))) {
            throw new \PowerTLA\Exception\InvalidTlaCluster();
        }

        $config = $this->findConfiguration(join("/", $apiIdentifier));

        if (empty($config) && empty($config["api"])) {
            throw new \PowerTLA\Exception\MissingApiSpecification();
        }

        $nsMap = json_decode($config["autoload"], true);

        $apiConfig = new \RESTling\Config\OpenApi();
        $apiConfig->loadConfigString($config["api"]);

        if (!empty($nsMap) && is_array($nsMap)) {
            $k = array_keys($nsMap);

            if (!empty($nsMap[$k[0]])) {
                $this->autoloader->setPsr4($k[0], $nsMap[$k[0]]);
            }
            $k = explode("\\", trim($k[0], "\\"));
        }

        if (empty($config["classname"])) {
            $tags = $apiConfig->getTags(true);
            $k = [];
            $apiConfig->setTagModel(array_merge($k, $tags));
        }
        else {
            $apiConfig->setTagModel(explode("\\", trim($config["classname"], '\\')));
        }

        $service->setApiConfig($apiConfig);
        $this->cfgLoaded = true;
    }

    public function loaded() {
        return $this->cfgLoaded;
    }

    protected function findConfiguration($apiId) {
        return [];
    }
}

?>
