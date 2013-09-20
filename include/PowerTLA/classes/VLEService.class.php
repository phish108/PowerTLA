<?php

class VLEService extends OAuthService {
    /**
     * @property $dberr
     *
     */
    private $dberr = false;
    
    /**
     * @property $VLE
     *
     */
    protected $VLE;

    /**
     * @method void
     */
    public function __construct($vleHandler) {
        // we need to find out which initialization we should run.
       
        
        if ( !empty($vleHandler) && $vleHandler->getDBHandler() ) {
            // pass the database handler down to OAuth handling
            parent::__construct($vleHandler->getDBHandler());
            $this->VLE = $vleHandler;
        }
        else {
            $this->dberr = true;
        }
    }

    /**
     * @method void
     */    
    protected function initializeRun() {
        if ( $this->dberr ) {
            $this->status = RESTling::UNINITIALIZED;
        }
        else {
            parent::initializeRun();
            
            $this->response_type = "json"; // we always talk JSON
             
            // now test if all the required plugins are running.
            if ($this->status === RESTling::OK
                && !$this->VLE->arePluginsActive()) {
                // plugin has been deactivated in the system administration.
                $this->status = RESTling::UNINITIALIZED;
            }
        }
    }
}

?>