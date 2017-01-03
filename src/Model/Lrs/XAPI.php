<?php

namespace PowerTLA\Model\LRS;

abstract class XAPI extends \RESTling\Model
{
    const VERB_VOIDED = "http://adlnet.gov/expapi/verbs/voided";

    public function xapiVersion($input) {
        $this->data = ["version" => $this->getVersion()];
    }

    /**
     * statement query interface
     */
    public function queryStatements($input) {

    }

    /**
     * stores one statements
     */
    public function storeSingleStatement($input) {

    }

    /**
     * stores one or more statements
     */
    public function storeStatements($input) {

    }

    /**
     * statement query interface
     */
    public function loadActivityDescription($input) {

    }

    /**
     * statement query interface
     */
    public function loadAgentResource($input) {

    }

    /**
     * return agent profiles
     */
    public function queryAgentProfiles($input) {

    }

    /**
     * return agent profiles
     */
    public function storeAgentProfile($input) {

    }

    /**
     * return agent profiles
     */
    public function updateAgentProfile($input) {

    }

    /**
     * return agent profiles
     */
    public function deleteAgentProfile($input) {

    }

    /**
     * return activity profiles
     */
    public function queryActivityProfiles($input) {

    }

    /**
     * return agent profiles
     */
    public function storeActivityProfile($input) {

    }

    /**
     * return agent profiles
     */
    public function updateActivityProfile($input) {

    }

    /**
     * return agent profiles
     */
    public function deleteActivityProfile($input) {

    }

    /**
     * return activity profiles
     */
    public function queryActivityStates($input) {

    }

    /**
     * return agent profiles
     */
    public function storeActivityState($input) {

    }

    /**
     * return agent profiles
     */
    public function updateActivityState($input) {

    }

    /**
     * return agent profiles
     */
    public function deleteActivityState($input) {

    }

    protected function getActorUserId($actorObject) {
        if (empty($actorObject)) {
            throw new \PowerTLA\Exception\MissingActorIdentifier();
        }

        if (is_string($actorObject)) {
            $actorObject = json_decode($actorObject, true);
        }

        $wfm = $this->getWebfingerModel($account[0]);
        if (!$wfm) {
            throw new \PowerTLA\Exception\MissingWebfingerModel();
        }

        if (array_key_exists("mbox", $actorObject)) {
            $account = explode(":", $actorObject["mbox"], 2);

            switch ($account[0]) {
                case "mailto":
                    return $wfm->findSubjectByEMail($account[1]);
                    break;
                case "acct":
                    // an acct also allows setting the context
                    return $wfm->findSubjectByAcct($account[1]);
                    break;
                default:
                    throw new \PowerTLA\Exception\InvalidActorIdentifier();
                    break;
            }
        }

        if (array_key_exists("openid", $actorObject)) {
            return $wfm->findSubjectByOpenId($actorObject["openid"]);
        }

        if (array_key_exists("account", $actor) &&
            array_key_exists("homepage", $actor["account"]) &&
            !empty($actor["account"]["homepage"]))
        {
            return $wfm->findSubjectByHomepage($actor["account"]["homepage"]);
        }

        // No actor identifier found in the actorObject.
        throw new \PowerTLA\Exception\MissingActorIdentifier();
    }

    /**
 	 * an implementing system MUST return an instance to its corresponding
     * webfinger model (e.g. \PowerTLA\Model\Identity\Webfinger\Moodle).
 	 *
 	 * @param type
 	 * @return void
	 */
	abstract protected function getWebfingerModel();
    abstract protected function findStatementByUuid($uuid);
    abstract protected function findDocumentByUuid($uuid);


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
                'pattern' => '/^\\/activities\\/profile(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'queryActivityProfiles',
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
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'since',
                        'in' => 'query',
                        'description' => 'Only ids of Profiles stored since the specified Timestamp (exclusive) are returned.
            ',
                        'type' => 'string',
                      ),
                      2 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'storeActivityProfile',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'post' =>
                  array (
                    'operationId' => 'updateActivityProfile',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'delete' =>
                  array (
                    'operationId' => 'deleteActivityProfile',
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/activities/profile',
              ),
              1 =>
              array (
                'pattern' => '/^\\/activities\\/state(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'queryActivityStates',
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
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this state.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'actor',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      2 =>
                      array (
                        'name' => 'registration',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state.
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'stateid',
                        'in' => 'query',
                        'description' => 'The id for this state, within the given context.
            Required for retrieving a single state
            ',
                        'type' => 'string',
                      ),
                      4 =>
                      array (
                        'name' => 'since',
                        'in' => 'query',
                        'description' => 'Only ids of states stored since the specified Timestamp
            (exclusive) are returned.
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'storeActivityState',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this state.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'actor',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      2 =>
                      array (
                        'name' => 'registration',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state.
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'stateid',
                        'in' => 'query',
                        'description' => 'The id for this state, within the given context.
            Required for retrieving a single state
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'post' =>
                  array (
                    'operationId' => 'updateActivityState',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this state.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'actor',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      2 =>
                      array (
                        'name' => 'registration',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state.
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'stateid',
                        'in' => 'query',
                        'description' => 'The id for this state, within the given context.
            Required for retrieving a single state
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'delete' =>
                  array (
                    'operationId' => 'deleteActivityState',
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this state.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'actor',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      2 =>
                      array (
                        'name' => 'registration',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state.
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/activities/state',
              ),
              2 =>
              array (
                'pattern' => '/^\\/agents\\/profile(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'queryAgentProfiles',
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
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'since',
                        'in' => 'query',
                        'description' => 'Only ids of Profiles stored since the specified Timestamp (exclusive) are returned.
            ',
                        'type' => 'string',
                      ),
                      2 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'The Agent associated with this profile document. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'storeAgentProfile',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'The Agent associated with this profile document. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'post' =>
                  array (
                    'operationId' => 'updateAgentProfile',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'The Agent associated with this profile document. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                  'delete' =>
                  array (
                    'operationId' => 'deleteAgentProfile',
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'profileID',
                        'in' => 'query',
                        'description' => 'The profile id associated with this Profile document.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                      1 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'The Agent associated with this profile document. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/agents/profile',
              ),
              3 =>
              array (
                'pattern' => '/^\\/activities(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'loadActivityDescription',
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
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'activityId',
                        'in' => 'query',
                        'description' => 'The Activity id associated with this state.
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/activities',
              ),
              4 =>
              array (
                'pattern' => '/^\\/statements(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'queryStatements',
                    'produces' =>
                    array (
                      0 => 'application/json',
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'statementId',
                        'in' => 'query',
                        'description' => 'Id of Statement to fetch
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'voidedStatementId',
                        'in' => 'query',
                        'description' => 'Id of voided Statement to fetch.
            ',
                        'type' => 'string',
                      ),
                      2 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'Filter, only return Statements for which the specified Agent
            or Group is the Actor or Object of the Statement.
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'verb',
                        'in' => 'query',
                        'description' => 'Filter, only return Statements matching the specified Verb id.
            ',
                        'type' => 'string',
                      ),
                      4 =>
                      array (
                        'name' => 'activity',
                        'in' => 'query',
                        'description' => 'Filter, only return Statements for which the Object of the
            Statement is an Activity with the specified id.
            ',
                        'type' => 'string',
                      ),
                      5 =>
                      array (
                        'name' => 'registration',
                        'in' => 'query',
                        'description' => 'Filter, only return Statements matching the specified
            registration id. Note that although frequently a unique
            registration will be used for one Actor assigned to one
            Activity, this cannot be assumed. If only Statements for a
            certain Actor or Activity are required, those parameters also
            need to be specified.
            ',
                        'type' => 'string',
                      ),
                      6 =>
                      array (
                        'name' => 'related_activities',
                        'in' => 'query',
                        'description' => 'Apply the Activity filter broadly. Include Statements for
            which the Object, any of the context Activities, or any of
            those properties in a contained SubStatement match the
            Activity parameter, instead of that parameter\'s normal
            behavior. Matching is defined in the same way it is for the
            "activity" parameter.
            ',
                        'type' => 'boolean',
                      ),
                      7 =>
                      array (
                        'name' => 'related_agents',
                        'in' => 'query',
                        'description' => 'Apply the Agent filter broadly. Include Statements for which
            the Actor, Object, Authority, Instructor, Team, or any of
            these properties in a contained SubStatement match the Agent
            parameter, instead of that parameter\'s normal behavior.
            Matching is defined in the same way it is for the "agent"
            parameter.
            ',
                        'type' => 'boolean',
                      ),
                      8 =>
                      array (
                        'name' => 'since',
                        'in' => 'query',
                        'description' => 'Only Statements stored since the specified Timestamp
            (exclusive) are returned.
            ',
                        'type' => 'string',
                      ),
                      9 =>
                      array (
                        'name' => 'until',
                        'in' => 'query',
                        'description' => 'Only Statements stored at or before the specified
            Timestamp are returned.
            ',
                        'type' => 'string',
                      ),
                      10 =>
                      array (
                        'name' => 'limit',
                        'in' => 'query',
                        'description' => 'Maximum number of Statements to return. 0 indicates return
            the maximum the server will allow.
            ',
                        'type' => 'integer',
                      ),
                      11 =>
                      array (
                        'name' => 'format',
                        'in' => 'query',
                        'description' => 'defines the statement format. Possible values are ids, exact,
            and cannonical
            ',
                        'type' => 'string',
                      ),
                      12 =>
                      array (
                        'name' => 'attachments',
                        'in' => 'query',
                        'description' => 'If true, the LRS uses the multipart response format and
            includes all attachments as described previously. If false,
            the LRS sends the prescribed response with Content-Type
            application/json and does not send attachment data.
            ',
                        'type' => 'boolean',
                      ),
                      13 =>
                      array (
                        'name' => 'ascending',
                        'in' => 'query',
                        'description' => 'If true, return results in ascending order of stored time
            ',
                        'type' => 'boolean',
                      ),
                    ),
                    'responses' =>
                    array (
                      200 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                  ),
                  'put' =>
                  array (
                    'operationId' => 'storeSingleStatement',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'statementId',
                        'in' => 'query',
                        'description' => 'Id of Statement to update
            ',
                        'type' => 'string',
                      ),
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                  ),
                  'post' =>
                  array (
                    'operationId' => 'storeStatements',
                    'consumes' =>
                    array (
                      0 => 'application/json',
                    ),
                    'responses' =>
                    array (
                      204 =>
                      array (
                        'description' => 'Successful response',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/statements',
              ),
              5 =>
              array (
                'pattern' => '/^\\/agents(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'loadAgentResource',
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
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'agent',
                        'in' => 'query',
                        'description' => 'The Agent associated with this state. (JSON object)
            ',
                        'type' => 'string',
                        'required' => true,
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/agents',
              ),
              6 =>
              array (
                'pattern' => '/^\\/about(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'xapiVersion',
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
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/about',
              ),
            );
    }

    /**
     * Returns the version of the API spec
     */
    final public function getVersion() {
        return '1.0.3';
    }

    /**
     * Returns the rsd protocol of the API
     */
    final public function getProtocol() {
        return 'gov.adlnet.xapi.communication';
    }

    private function generateUUID()
    {
        $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0x0fff ) | 0x4000,
                        mt_rand( 0, 0x3fff ) | 0x8000,
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ),
                        mt_rand( 0, 0xffff ));

        $this->statement["id"] = trim($uuid);
        return $this->statement["id"];
    }

    private function generateTimestamp()
    {
        $dt = new DateTime('NOW');
        return $dt->format(DateTime::ISO8601);
    }
}

?>
