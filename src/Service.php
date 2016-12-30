<?php
namespace PowerTLA;

class Service extends \RESTling\OpenAPI {
    private $specLoader = [];
    protected $platform;

    public function addApiLoader($loader) {
        if (!($loader && $loader instanceof \PowerTLA\Interfaces\Loader)) {
            throw new Exception\LoaderInterfaceMismatch();
        }
        $this->specLoader[] = $loader;
    }

    /**
 	 * Extends the tag model loading for finding local and distribution level
     * models.
     *
     * Local models are specific to the platform and even to the instance of
     * PowerTLA. Local models are always in the \Local\PowerTLA namespace.
     *
     * Distribution-level models are packaged with PowerTLA. distribution-level
     * models are always in the \PowerTLA\Model namespace. Distribution-level
     * models also have sub-variant for each supported plattform.
     *
     * This method first tries to load a local model and only if this fails,
     * then it falls back to the distribution-level.
 	 *
 	 * @param array $tags contain the TLA cluster and the function of a model
 	 * @return void
	 */
	protected function loadTagModel($tags) {
        if (is_array($tags) && !empty($tags)) {
            $localModel = array_merge(["Local", "PowerTLA"], $tags);
            $distModel  = array_merge(["PowerTLA", "Model"], $tags, [$this->platform]);

            // Local models have preference over distribution models.
            parent::loadTagModel($localModel);
            parent::loadTagModel($distModel);
        }
    }

    protected function verifyModel() {
        // figure out path info root
        if (!array_key_exists("PATH_INFO", $_SERVER)) {
            throw new Exception\MissingPathRoot();
        }

        $aPath = explode('/', $_SERVER["PATH_INFO"]);
        $e = array_shift($aPath);
        if (count($aPath) < 2) {
            throw new Exception\MissingOperationCluster();
        }

        $aConfig = [];
        $aConfig[] = strtolower(array_shift($aPath));
        $aConfig[] = strtolower(array_shift($aPath));

        // check if we find an OpenApi Definition
        $this->findApiSpecification($aConfig);

        // reset path_info
        array_unshift($aPath, $e);
        $_SERVER["PATH_INFO"] = join('/', $aPath);

        // pass down to RESTling\OpenApi
        parent::verifyModel();
    }

    /**
 	 * This method loads the api specification for an API Identifier.
     *
     * An API identifier is an array containing the TLA cluster and protocol
     * name.
     *
     * Valid TLA Cluster names are
     * - identity
     * - lrs
     * - content
     * - orchestration
 	 *
 	 * @param array $apiIdentifier
 	 * @return void
     * @throws PowerTLA\Exception\MissingApiSpecification if no specification was found
	 */
	private function findApiSpecification($apiIdentifier) {
        $loaded = false;
        foreach ($this->specLoader as $loader) {
            try {
                $loader->findAndLoad($this, $apiIdentifier);
                $loaded = ($loaded || $loader->loaded());
                if ($loaded) {
                    break;
                }
            }
            catch (Exception $err) {
                $tmp = $err->getMessage();
            }
        }
        if (!$loaded) {
            throw new Exception\MissingApiSpecification();
        }
    }
}

?>
