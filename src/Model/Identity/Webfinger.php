<?php

namespace PowerTLA\Model\Identity;

abstract class Webfinger extends \RESTling\Model
{
    protected $resource;
    protected $userid = null;
    protected $acct = null;
    protected $aliases =[];
    protected $context = [];
    protected $type = '';

    protected function clear() {
        $this->resource = null;
        $this->userid   = null;
        $this->acct     = null;
        $this->aliases  = [];
        $this->context  = [];
        $this->type     = "";
    }

    public function getResource($input) {
        $this->clear();
        $this->data = [];

        $resource = $input->getParameter("resource", "query");

        if (empty($resource)) {
            // find out about oneself
            $userid = $input->getUser();
            $subject = $this->getUsername($userid);
            if (empty($subject)) {
                throw new \RESTling\Exception\BadRequest();
            }
            $resource = urlencode($subject);
        }
        else {
            $subject = urldecode($resource);
        }

        $this->findSubjectByUri($subject);

        if (!$this->resource) {
            throw new \RESTling\Exception\NotFound();
        }

        // prepare response
        $this->data["subject"] = $subject;
        $this->data["links"] = [];

        $reqUri = trim($_SERVER["REQUEST_URI"], "/");

        if ($input->hasActiveUser($this->userid)) {
            // I try to request my own data
            $this->data["aliases"] = $this->loadAliases($subject);

            $this->data["links"] = ["https://xapi.li/webfinger/rel/profile" => "$reqUri/profile/$resource"];
            $this->data["properties"] = $this->loadSubjectProperties();
        }

        if ($this->hasSharedContext($input->getUser())) {
            $this->data["aliases"] = $this->loadContextAliases($input->getUser(), $subject);

            $this->data["links"] = ["https://xapi.li/webfinger/rel/profile" => "$reqUri/profile/$resource"];
        }
    }

    public function getRelations($input) {
        if ($input->hasUser()) {
            $this->data = [];
            $this->data[] = "https://xapi.li/webfinger/rel/profile";
        }
    }

    public function addAccount($input) {
        throw new \RESTling\Exception\NotImplemented();
        // $body = $input->getBody();
        // if (array_key_exists("context", $body)) {
        //     $this->context = $body["context"];
        // }
        //
        // $users = $input->getUser();
        // if (!empty($users)) {
        //     $this->userid = $users[0];
        // }
        //
        // $this->generateContext();
        // $this->generateAcct();
    }

    public function getProfilePage($input) {
        $acct = urldecode($input->getParameter("acctUri", "path"));

        $this->findSubjectByAcct($acct);

        if ($this->userid <= 0) {
            throw new \RESTling\Exception\NotFound();
        }

        if (!($input->hasActiveUser($this->userid) || $this->hasSharedContext($input->getUser()))) {
            throw new \RESTling\Exception\Forbidden();
        }

        // TODO add access scoping
        // by default just pass the user name and not extra information
        $profile = $this->getSubjectProfile();

        foreach ($profile as $k => $v) {
            $this->data[$k] = $v;
        }
    }

    public function addAccountResource($input) {
        throw new \RESTling\Exception\NotImplemented();
    }

    // abstract public function findSubjectByUserId($userid);
    abstract public function getUsername($useridList);
    abstract public function findSubjectByEMail($email);
    abstract public function findSubjectByAcct($acct);
    abstract public function findSubjectByUserId($userId);
    abstract public function findSubjectByOpenId($openIdUri);
    abstract public function findSubjectByHomepage($homepageUri);
    // abstract public function findAcctByUserContext($userid, $context);

    /**
 	 * Tries to find a resource by a Link URI. It is used for regular objects
     * not users.
 	 *
 	 * @param string $resourceUri
 	 * @return void
	 */
	// abstract public function findSubjectByLink($resourceUrl);

    abstract protected function getSystemId();

    abstract protected function getSubjectProperties();
    abstract protected function getSubjectProfile();

    abstract protected function hasSharedContext($useridList);

    abstract protected function loadContextAliases($otherUserId, $excludeSubject);
    abstract protected function loadAliases($excludeSubject);

    /**
 	 * Creates a new acct instance using the member properties.
 	 *
 	 * @return void
	 */
	// abstract protected function storeAcct();

    public function findSubjectByUri($acct){
        if (strpos($acct, "acct:") === 0) {
            // search for users
            $this->findSubjectByAcct($acct);
            $this->type = "user";
        }
        elseif (strpos($acct, "mailto:") === 0) {
            $email = array_pop(explode(":", $acct));
            $this->findSubjectByEMail($email);
            $this->type = "user";
        }
        else {
            // search for resources
            $this->type = "resource";
        }
    }

    protected function generateAcct() {
        if ($this->userid) {
            $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand( 0, 0xffff ),
                            mt_rand( 0, 0xffff ),
                            mt_rand( 0, 0xffff ),
                            mt_rand( 0, 0x0fff ) | 0x4000,
                            mt_rand( 0, 0x3fff ) | 0x8000,
                            mt_rand( 0, 0xffff ),
                            mt_rand( 0, 0xffff ),
                            mt_rand( 0, 0xffff ));

            $newId = sha1($uuid . $this->userid);

            // add my system id
            $this->acct = $newId . "@" . $this->getSystemId();
            $this->storeAcct();
        }
    }

    /**
     * Returns the pathmap of the model.
     *
     * This is automatically generated from the API specification. You can
     * safely ignore this part.
     *
     * Note: on API changes, this method may change too.
     */
    final public function getPathMap() {
        return array (
              0 =>
              array (
                'pattern' => '/^\\/profile\\/([^\\/]+)(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getProfilePage',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                      204 =>
                      array (
                        'description' => 'Successful response for profile confirmation outside of the context',
                      ),
                      404 =>
                      array (
                        'description' => 'acctUri is not found in the registry',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'acctUri',
                        'in' => 'path',
                        'description' => 'the webfinger acct-uri to test. The acctUri MUST be percent encoded, as per
            [Section 2.1 of RFC3986](https://tools.ietf.org/html/rfc3986#section-2.1).
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'addAccountResource',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      201 =>
                      array (
                        'description' => 'Successful Create response',
                      ),
                      404 =>
                      array (
                        'description' => 'acctUri is not found in the registry',
                      ),
                      409 =>
                      array (
                        'description' => 'the provided resource Uri exists outside of the acctUri context.',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'acctUri',
                        'in' => 'path',
                        'description' => 'the webfinger acct-uri to test. The acctUri MUST be percent encoded, as per
            [Section 2.1 of RFC3986](https://tools.ietf.org/html/rfc3986#section-2.1).
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                  0 => 'acctUri',
                ),
                'path' => '/profile/{acctUri}',
              ),
              1 =>
              array (
                'pattern' => '/^\\/profile(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'put' =>
                  array (
                    'operationId' => 'addAccount',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      201 =>
                      array (
                        'description' => 'Successful Create response',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/profile',
              ),
              2 =>
              array (
                'pattern' => '/^\\/rel(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getRelations',
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful Create response',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/rel',
              ),
              3 =>
              array (
                'pattern' => '/^\\/(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'getResource',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                      404 =>
                      array (
                        'description' => 'Resource is not found in the registry',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'resource',
                        'in' => 'query',
                        'description' => 'the webfinger acct uri to test
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'rel',
                        'in' => 'query',
                        'description' => 'limit the resulting relations relation type. Multiple occurences possible.
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/',
              ),
            );
    }

    /**
     * Returns the version of the API spec
     */
    final public function getVersion() {
        return '1.0.0';
    }

    /**
     * Returns the rsd protocol of the API
     */
    final public function getProtocol() {
        return 'org.ietf.webfinger';
    }
}

?>
