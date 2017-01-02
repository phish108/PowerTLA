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

        // check if we find an OpenApi Definition via a Loader
        $loaded = false;
        foreach ($this->specLoader as $loader) {
            try {
                $loader->findAndLoad($this, $aConfig);
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

        // reset path_info
        if (empty($aPath)) {
            // set the PATH_INFO to '/'
            array_unshift($aPath, $e);
        }

        array_unshift($aPath, $e);
        $_SERVER["PATH_INFO"] = join('/', $aPath);

        // pass down to RESTling\OpenApi
        parent::verifyModel();
    }
}

?>
