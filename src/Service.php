<?php
namespace PowerTLA;

class Service extends \RESTling\OpenAPI {
    private $specLoader = [];

    public function addLoader($loader) {
        if (!($loader && $loader instanceof \PowerTLA\Interfaces\Loader)) {
            throw new Exception\LoaderInterfaceMismatch();
        }
        $this->specLoader[] = $loader;
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
        $this->findApiDefinition($aConfig);

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
