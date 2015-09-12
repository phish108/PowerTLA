<?php
class ClientService extends VLEService
{
    private $provider;

    public static function apiDefinition($prefix, $name="", $link="")
    {
        return parent::apiDefinition($prefix, "powertla.identity.client", "client.php");
    }

    protected function initializeRun()
    {
        $this->log("service started");
        $this->provider = $this->VLE->getClientProvider();
        // PUT is a public interface!
        $this->VLE->getSessionValidator()->setMethods(array("put" => false));

        // The client service forbids access using bearer or MAC tokens
        // NOTE: the reject is not performed for the PUT request.
        $this->VLE->getSessionValidator()->rejectTokenType("Bearer");
        $this->VLE->getSessionValidator()->rejectTokenType("MAC");
    }

    /**
     * @method get()
     *
     * enables GET requests for a client token.
     * If the client/request token is valid, it will respond with 204 (NO CONTENT).
     * If the token is invalid or not existing, it will respond with 403 (Authentication required).
     */
    protected function get()
    {
        // This is deliberately left empty
    }

    /**
     * @method validateData()
     *
     * checks if all required data is present for the PUT request.
     */
    protected function validateData()
    {
        $this->log("validate data");
        if ($this->operation == "put" &&
            isset($this->inputData) &&
            ($this->inputDataType == "application/json" ||
             $this->inputDataType == "application/x-www-form-urlencoded") &&
            !(array_key_exists("client", $this->inputData) &&
              array_key_exists("domain", $this->inputData) &&
              !empty($this->inputData["client"]) &&
              !empty($this->inputData["domain"])))
        {
            $this->log("missing data " . json_encode($this->inputData));
            $this->status = RESTling::BAD_DATA;
            $this->data = array("message" => "Missing Data");
        }
    }

    // generate a new client token
    protected function put()
    {
        $this->data = $this->provider->addClient($this->inputData["client"],
                                                 $this->inputData["domain"]);
    }

    // delete client token proactively
    protected function delete()
    {
        $aP = $this->VLE->getAuthProvider->getTokenInformation();
        $this->provider->eraseClient($aP["client"], $aP["domain"]);
    }
}
?>
