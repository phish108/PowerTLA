<?php
namespace PowerTLA\Model\Identity;

abstract class OAuth2 extends \RESTling\Model
{
    protected $trustAssertion;
    protected $stateInfo = null;

    public function initAuthorization($input) {
        // get target
        $myredirectUri = $this->getMyCallbackUri();
        $idp           = $this->getIDP($input->get("idp", "query"));

        if (!$idp || $ipd["flow"] == "assertion") {
            throw new \RESTling\Exception\NotFound();
        }

        // create a new state
        $param = [
            "redirect_uri" => urlencode($myredirectUri),
            "client_id"    => urlencode($idp["client_id"]),
            "response_type" => urlencode("code id_token"),
            "scope"         => urlencode("openid profile email")
        ];
        $param["state"] = urlencode($this->prepareState($idp));

        $res = [];
        foreach ($param as $k => $v) {
            $res[] = "$k=$v";
        }
        $this->redirect($idp["url"] . "?" . join('&', $res));
    }

    // get /cb operation handler
    public function oidcCallback($input) {
        if ($error = $input->get("error", "query")) {
            throw new \RESTling\Exception\Forbidden();
        }

        $this->verifyState($input->get("state", "query"));

        if ($code = $input->get("code", "query")) {
            $this->requestAuthorizationToken($input);
        }
        elseif ($token = $input->get("id_token", "query")) {
            // received a successful authorization
            // process the incoming IdToken
            $loader = new \Jose\Loader();
            try {
                $jwt = $loader->load($token);
            }
            catch (Exception $err) {
                throw new \RESTling\Exception\Security\InvalidJwt();
            }

            $this->handleIdToken($this->decryptJWE($jwt));

            if (empty($this->stateInfo["token"]) &&
                empty($this->stateInfo["token_id"]) &&
                empty($this->stateInfo["refresh_id"])) {
                $this->redirectHome();
            }
            // no errors? we grant access to the user
            $access_token  = $input->get("access_token", "query");
            $refresh_token = $input->get("refresh_token", "query");
            $exprires      = $input->get("expires_in", "query");

            // keep the tokens for reference
            $this->storeToken($access_token, $refesh_token, $expires);

            // hand the tokens to the client
            $this->data = [
                "access_token"  => $access_token,
                "refresh_token" => $refresh_token,
                "expires_in"    => $expires,
                "token_type"    => "Bearer"
            ];
        }
    }

    // /token endpoint operation handler
    final public function handleTokenRequest($input) {
        $gType = $input->get("grant_type", "formData");

        // deconstruct type URIs
        $gType = array_pop(explode(":", $gType));
        $gType = str_replace(["-", "+"], "_", $gType);

        // call the actual handler
        $fname = "handle_$gType";
        $this->$fname($input);
    }

    // /revoke endpoint operation handler
    public function revokeToken($input) {
        $hint = $input->get("token_hint", "body");
        $token = $input->get("token", "body");
        if ($hint != "access_token" && $hint != "refresh_token") {
            throw new \RESTling\Exception\BadRequest();
        }

        if (empty($token)) {
            throw new \RESTling\Exception\BadRequest();
        }

        // FIXME: if an external Authority is set, revoke the token there, too
        $this->deleteTokenEntry($hint, $token);
    }

    abstract protected function loadState($state);
    abstract protected function storeState($state, $attr);
    abstract protected function grantSecondaryTokens($issuer) ;
    abstract protected function grantAccessTokens($authority, $userid);
    abstract protected function storeToken($aT, $rT, $ex);
    abstract protected function redirectHome();
    abstract protected function deleteToken($field, $token);
    abstract protected function getToken($field, $token);
    abstract protected function getPrivateKey();
    abstract protected function getSharedKey($kid, $jku);
    abstract protected function getIssuerKey($kid, $iss);
    abstract protected function verifyIssuer($iss, $id);
    abstract protected function handleUser($userClaims, $azp_id);

    protected function verifyState($state) {
        $stateObj = $this->loadState($state);

        if (empty($stateObj)) {
            throw new \RESTling\Exception\Forbidden();
        }

        if (!empty($stateObj["token"])) {
            $loader = new \Jose\Loader();
            try {
                $jwt = $loader->load($stateObj["token"]);
            }
            catch (Exception $err) {
                throw new \RESTling\Exception\Security\InvalidJwt();
            }

            $this->trustAssertion = $jwt;
        }

        $this->stateInfo = $stateObj;
    }

    protected function grantAccessForId($userIdInformation) {
        if (empty($userIdInformation)) {
            throw new \RESTling\Exception\Forbidden();
        }
        if (!array_key_exists("sub", $userIdInformation)) {
            throw new \RESTling\Exception\Forbidden();
        }
    }

    protected function getSupportedClaims() {
        // by default we support all claims
        return [
                "sub",
                "name",
                "given_name",
                "family_name",
                "middle_name",
                "nickname",
                "preferred_username",
                "profile",
                "picture",
                "website",
                "email",
                "email_verified",
                "gender",
                "brithdate",
                "zoneinfo",
                "locale",
                "phone_number",
                "phone_number_verified",
                "address",
                "updated_at",
                "formatted",
                "street_address",
                "locality",
                "region",
                "postal_code",
                "country"
        ];
    }

    protected function findTargetAuthority($azp) {
        throw new RESTling\Exception\Forbidden();
    }

    private function handleIdToken($jwt, $input) {
        if ($jwt instanceof \Jose\Object\JWS) {
            $kid = $jwt->getSignature(0)->getProtectedHeader('kid');
            $jku = $jwt->getSignature(0)->getProtectedHeader('jku');
            $alg = $jwt->getSignature(0)->getProtectedHeader('alg');

            $keyAttr = [
                'use' => 'sig',
            ];

            // 7a ask JOSE Key Context (for $kid or $jku) from model
            $keyId = $kid;
            $keyAttr['kid'] = $kid;

            if (empty($alg)) {
                throw new \RESTling\Exception\Security\InvalidJwt();
            }

            if (empty($kid) && empty($jku)) {
                throw new \RESTling\Exception\Security\KeyIdMissing();;
            }

            if (!method_exists($this, 'getSharedKey')) {
                throw new \RESTling\Exception\Security\SharedKeyValidationUnsupported();
            }

            list($azp, $keyString) = $model->getSharedKey($keyId, $jku);
            if (empty($keyString)) {
                throw new \RESTling\Exception\Security\SharedKeyMissing();
            }

            $key = \Jose\Factory\JWKFactory::createFromKey($keyString,
                                                           null,
                                                           $keyAttr);
            if (!$key) {
                throw new \RESTling\Exception\Security\KeyBroken();;
            }

            $jwk_set = new \Jose\Object\JWKSet();
            $jwk_set->addKey($key);

            // 8 verify JWS signature
            $verifier = \Jose\Verifier::createVerifier([$alg]);
            try {
                $verifier->verifyWithKeySet($jwt, $jwk_set, null, null);
            }
            catch (Exception $err) {
                throw new \RESTling\Exception\Security\TokenRejected();
            }

            // 9  verify iss claim with key context
            $iss = $jwt->getClaim('iss');

            if (empty($iss)) {
                throw new \RESTling\Exception\Security\MissingIssuer();
            }

            if (method_exists($this, "verifyIssuer")) {
                try {
                    $model->verifyIssuer($iss, $azp);
                }
                catch (Exception $err){
                    throw new \RESTling\Exception\Security\IssuerRejected();
                }
            }

            // 10 verify that aud points to service URL
            $aud = $jwt->getClaim('aud');

            if (empty($aud)) {
                throw new \RESTling\Exception\Security\MissingAudience();
            }

            $myUrl = 'http' . ($_SERVER['HTTPS'] ? "s" : "") . "://" . (empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];

            if ($aud !== $myUrl) {
                throw new \RESTling\Exception\Security\AudienceRejected();
            }

            $idClaims = $this->getSupportedClaims();
            $userClaims = [];
            foreach ($idClaims as $claim) {
                if ($cdata = $jwt->getClaim($claim)) {
                    $userClaims[$claim] = $cdata;
                }
            }

            // need for validating assertion that is used to trigger the authorization request
            // this part runs ONLY if the OP responded to a primary token assertion.
            $this->validateTrustAssertion($userClaims);

            // No errors? we can accept the user.
            // the plugin MAY reject certain users
            // we need the azp, so we may change the auth method
            $userid = $this->handleUser($userClaims, $azp);
            return [$azp, $userid];
        }
        return [null, null];
    }

    private function validateTrustAssertion($userClaims) {
        if ($this->trustAssertion instanceof \Jose\Object\JWS) {
            // in this case we need to validate the ID Token against the
            // trust agent
            if ($this->trustAssertion->getClaim("sub") != $userClaims["sub"]) {
                throw new \RESTling\Exception\Forbidden();
            }

            $kid = $this->trustAssertion->getSignature(0)->getProtectedHeader('kid');
            $alg = $this->trustAssertion->getSignature(0)->getProtectedHeader('alg');

            $kid = array_pop(explode(":", $kid));

            $key = "";
            $fname = "get_".$kid."_param";
            $jwk_set = $this->$fname($userClaims);

            // verify the assertion
            $verifier = \Jose\Verifier::createVerifier([$alg]);
            try {
                $verifier->verifyWithKeySet($this->trustAssertion, $jwk_set, null, null);
            }
            catch (Exception $err) {
                throw new \RESTling\Exception\Security\TokenRejected();
            }
        }
    }

    private function get_email_param($userClaims) {
        return $this->getKeyFromString($userClaims["email"]);
    }

    private function get_profile_param($userClaims) {
        $claims = [
            "name",
            "family_name",
            "given_name",
            "middle_name",
            "nickname",
            "preferred_username",
            "profile",
            "picture",
            "website",
            "gender",
            "birthdate",
            "zoneinfo",
            "locale",
            "updated_at"
        ];
        $kstring = "";
        foreach ($claims as $c) {
            if (!empty($userClaims[$c])) {
                $kstring .= $c;
            }
        }
        return $this->getKeyFromString($kstring);
    }

    private function get_address_param($userClaims) {
        $claims = [
            "formatted",
            "street_address",
            "locality",
            "region",
            "postal_code",
            "country"
        ];
        $kstring = "";
        foreach ($claims as $c) {
            if (!empty($userClaims[$c])) {
                $kstring .= $c;
            }
        }
        return $this->getKeyFromString($kstring);
    }

    private function get_phone_param($userClaims) {
        // phone_number
        return $this->getKeyFromString($userClaims["phone_number"]);
    }

    private function getKeyFromString($keyString) {
        $keyAttr = [
            'use' => 'sig'
        ];

        $key = \Jose\Factory\JWKFactory::createFromKey($keyString,
                                                       null,
                                                       $keyAttr);
        if (!$key) {
            throw new \RESTling\Exception\Security\KeyBroken();;
        }

        $jwk_set = new \Jose\Object\JWKSet();
        $jwk_set->addKey($key);

        return $jwk_set;
    }

    private function handle_jwt_bearer($input) {
        // handle assertion
        $token = $input->get("assertion", "body");

        $loader = new \Jose\Loader();
        try {
            $jwt = $loader->load($token);
        }
        catch (Exception $err) {
            throw new \RESTling\Exception\Security\InvalidJwt();
        }

        // check if the assertion is encrypted
        $jwt = $this->decryptJWE($jwt);

        if ($jwt instanceof \Jose\Object\JWS) {
            // check if we have to deal with an client assertion
            $kid = $jwt->getSignature(0)->getProtectedHeader('kid');
            if (!empty($kid) && $kid == "urn:oidc:email") {
                $this->handleAgentAssertion($kid, $jwt); // ends in a redirect
            }
            elseif ($sub = $jwt->getClaim('sub')) {
                // a regular assertion coming from a registered source
                // $this->verifyState($input->get("state", "formData"));

                list($azp, $userid) = $this->handleIdToken($jwt);

                list($access_token, $refresh_token, $expires) = $this->grantAccessTokens($azp, $userid);

                $this->data = [
                    "access_token"  => $access_token,
                    "refresh_token" => $refresh_token,
                    "expires_in"    => $expires,
                    "token_type"    => "Bearer"
                ];
            }
            elseif ($iss = $jwt->getClaim('iss')) {
                // secondary token assertions do not contain a sub hint.
                // they will use the same sub value as the primary key
                $this->handleSecondaryToken($jwt, $input);
            }
        }
    }

    protected function handleAgentAssertion($kid, $jwt) {
        $azp = $jwt->getClaim("azp"); // link to the authorization party

        $authParam = $this->findTargetAuthority($azp); // lookup the target or throw an exception!

        if (!$authParam || $authParam["flow"] == "assertion") {
            throw new \RESTling\Exception\NotFound();
        }

        // keep the compact JWS String, so we can verify later
        $authParam["state"]  = $this->prepareState($authParam); // create a new state
        $authParam["prompt"] = 'none';

        if (!array_key_exists("scope", $authParam)) {
            $authParam["scope"] = "openid";
        }
        $kid = array_pop(explode(":", $kid));
        $authParam["scope"] .= " $kid";

        if (!array_key_exists("response_type", $authParam)) {
            $authParam["response_type"] = "code id_token token";
        }

        $query = [];
        foreach ($authParam as $p => $v) {
            $query[] = urlencode($p) . '=' . urlencode($v);
        }
        $azp .= "?" . join("&", $query);
        $this->redirect($azp); // throw the redirect exception!
    }

    protected function prepareState($idp) {
        $data = join("",[time(), $idp["client_id"]]);
        $state = $this->randomSelection(hash_hmac("sha256", $data, $this->randomString(5)), 20);

        $this->storeState($state, ["azp_id" => $idp["id"]]);
    }

    protected function handleSecondaryToken($jwt, $input) {
        // get the authorization token
        $auth = $input->get("Authorization", "header");
        if (empty($auth) && strpos($auth, "Bearer ") !== 0) {
            throw new \RESTling\Exception\Forbidden();
        }

        list($x, $token) = explode(" ", $auth, 2);
        if (empty($token)) {
            throw new \RESTling\Exception\Forbidden();
        }

        $issuer = $this->getToken("access_token", $token);
        // verify the token expiration
        $ts = time();
        if ($issuer["expires"] > $ts) {
            // give the client a chance to refresh
            throw new \RESTling\Exception\Unauthorized();
        }

        $kid = $jwt->getSignature(0)->getProtectedHeader('kid');
        $alg = $jwt->getSignature(0)->getProtectedHeader('alg');

        $key = $this->getIssuerKey($kid, $issuer["id"]);
        $jwk_set = $this->getKeyFromString($key);

        $kid = array_pop(explode(":", $kid));

        // verify the assertion
        $verifier = \Jose\Verifier::createVerifier([$alg]);
        try {
            $verifier->verifyWithKeySet($this->trustAssertion, $jwk_set, null, null);
        }
        catch (Exception $err) {
            throw new \RESTling\Exception\Security\TokenRejected();
        }

        // we accept the assertion
        // TODO cover the SCOPE!
        list($access_token, $refresh_token, $expires) = $this->grantSecondaryTokens($issuer);
        $this->data = [
            "access_token"  => $access_token,
            "refresh_token" => $refresh_token,
            "expires_in"    => $expires,
            "token_type"    => "Bearer"
        ];
    }

    private function handle_authorization_code($input) {
        throw new \RESTling\Exception\NotImplemented();
    }

    private function handle_password($input) {
        throw new \RESTling\Exception\NotImplemented();
    }

    private function handle_client_credentials($input) {
        throw new \RESTling\Exception\NotImplemented();
    }

    private function requestCodeToken($input) {
        throw new \RESTling\Exception\NotImplemented();
    }

    private function decryptJWE($jwt) {
        if ($jwt instanceof \Jose\Object\JWE) {
            if (empty($kid) && empty($jku)) {
                // 3a check if we need to load a private key
                if (!preg_match('/^RSA/', $alg)) {
                    throw new \RESTling\Exception\Security\JwaUnsupported();
                }

                if (!method_exists($this, 'getPrivateKey')) {
                    throw new \RESTling\Exception\Security\PrivateKeyDecryptionUnsupported();
                }
                if (!($keyString = $model->getPrivateKey())) {
                    throw new \RESTling\Exception\Security\PrivateKeyMissing();
                }
            }
            else {
                // 3c  load kid or jku from JOSE header if present
                if (!method_exists($model, 'getSharedKey')) {
                    throw new \RESTling\Exception\Security\SharedKeyDecryptionUnsupported();
                }

                $keyId = $kid;
                if (empty($kid)) {
                    $keyId = $jku;
                }
                else {
                    $keyAttr['kid'] = $kid;
                }

                // 3d ask JOSE Key Context (for $kid or $jku) from model
                list($azp, $keystring) = $model->getSharedKey($keyId, $jku);
                if (empty($keyString)) {
                    throw new \RESTling\Exception\Security\SharedKeyMissing();
                }
            }

            $key = \Jose\Factory\JWKFactory::createFromKey($keyString,
                                                           null,
                                                           $keyAttr);
            if (!$key) {
                throw new \RESTling\Exception\Security\KeyBroken();
            }

            $jwk_set = new \Jose\Object\JWKSet();
            $jwk_set->addKey($key);
            $decrypter = \Jose\Decrypter::createDecrypter([$alg], [$enc],['DEF', 'ZLIB', 'GZ']);

            try {
                $decrypter->decryptUsingKeySet($jwt, $jwk_set, null);
            }
            catch (Exception $err) {
                throw new \RESTling\Exception\Security\DecryptionFailed();
            }

            $payload = $jwt->getPayload();
            if (!$payload) {
                throw new \RESTling\Exception\Security\MissingPayload();
            }

            if (is_array($payload) && array_key_exists('signatures', $payload)) {
                // 5a update $jwt hold the embedded JWS
                $jwt = \Jose\Util\JWSLoader::loadSerializedJsonJWS($payload);
                if (!$jwt || !($jwt instanceof \Jose\Object\JWS)) {
                    throw new \RESTling\Exception\Security\MissingJwt();
                }
            }
            else {
                throw new \RESTling\Exception\Security\InvalidJwt();;
            }
        }
        return $jwt;
    }

    // authorization code flow is presently unsupported
    private function requestAuthorizationToken($input) {
        throw new \RESTling\Exception\NotImplemented();
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
                'pattern' => '/^\\/revoke(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'post' =>
                  array (
                    'operationId' => 'revokeToken',
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
                      401 =>
                      array (
                        'description' => 'Authorization expired',
                      ),
                      403 =>
                      array (
                        'description' => 'Authorization failed',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'token',
                        'in' => 'formData',
                        'description' => 'OAuth2 access or refresh token
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'token_hint',
                        'in' => 'formData',
                        'description' => 'Informs which token type is used (either access or refresh token)
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/revoke',
              ),
              1 =>
              array (
                'pattern' => '/^\\/token(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'post' =>
                  array (
                    'operationId' => 'handleTokenRequest',
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
                      302 =>
                      array (
                        'description' => 'Forwards a subject to the registred authorization service',
                      ),
                      401 =>
                      array (
                        'description' => 'Authorization expired',
                      ),
                      403 =>
                      array (
                        'description' => 'Authorization failed',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'grant_type',
                        'in' => 'formData',
                        'description' => 'OAuth2 Grant Type
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'state',
                        'in' => 'formData',
                        'description' => 'OAuth2 state string
            ',
                        'type' => 'string',
                      ),
                      2 =>
                      array (
                        'name' => 'code',
                        'in' => 'formData',
                        'description' => 'OAuth2 Refresh Token
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'redirect_uri',
                        'in' => 'formData',
                        'description' => 'OAuth2 parameter
            ',
                        'type' => 'string',
                      ),
                      4 =>
                      array (
                        'name' => 'client_id',
                        'in' => 'formData',
                        'description' => 'OAuth2 parameter
            ',
                        'type' => 'string',
                      ),
                      5 =>
                      array (
                        'name' => 'refresh_token',
                        'in' => 'formData',
                        'description' => 'OAuth2 parameter
            ',
                        'type' => 'string',
                      ),
                      6 =>
                      array (
                        'name' => 'assertion',
                        'in' => 'formData',
                        'description' => 'OAuth2 assertion token
            ',
                        'type' => 'string',
                      ),
                      7 =>
                      array (
                        'name' => 'scope',
                        'in' => 'formData',
                        'description' => 'OAuth2 client credentials scope
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/token',
              ),
              2 =>
              array (
                'pattern' => '/^\\/cb(?:\\/(.+))?$/',
                'pathitem' =>
                array (
                  'get' =>
                  array (
                    'operationId' => 'oidcCallback',
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
                      403 =>
                      array (
                        'description' => 'Authorization failed',
                      ),
                    ),
                    'parameters' =>
                    array (
                      0 =>
                      array (
                        'name' => 'code',
                        'in' => 'query',
                        'description' => 'OAuth2 Access code
            ',
                        'type' => 'string',
                      ),
                      1 =>
                      array (
                        'name' => 'state',
                        'in' => 'query',
                        'description' => 'OAuth2 state string
            ',
                        'type' => 'string',
                      ),
                      2 =>
                      array (
                        'name' => 'error',
                        'in' => 'query',
                        'description' => 'OAuth2 Refresh Token
            ',
                        'type' => 'string',
                      ),
                      3 =>
                      array (
                        'name' => 'error_description',
                        'in' => 'query',
                        'description' => 'OAuth2 Refresh Token
            ',
                        'type' => 'string',
                      ),
                      4 =>
                      array (
                        'name' => 'access_token',
                        'in' => 'query',
                        'description' => 'OAuth2 Refresh Token
            ',
                        'type' => 'string',
                      ),
                      5 =>
                      array (
                        'name' => 'id_token',
                        'in' => 'query',
                        'description' => 'OIDC ID Token
            ',
                        'type' => 'string',
                      ),
                      6 =>
                      array (
                        'name' => 'refresh_token',
                        'in' => 'query',
                        'description' => 'OAuth2 Refresh Token
            ',
                        'type' => 'string',
                      ),
                      7 =>
                      array (
                        'name' => 'expires_in',
                        'in' => 'query',
                        'description' => 'expires_in seconds
            ',
                        'type' => 'string',
                      ),
                      8 =>
                      array (
                        'name' => 'token_type',
                        'in' => 'query',
                        'description' => 'token type
            ',
                        'type' => 'string',
                      ),
                    ),
                  ),
                ),
                'vars' =>
                array (
                ),
                'path' => '/cb',
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
        return 'org.ieft.oauth2';
    }

    final protected function randomString($length=10) {
        $resstring = "";
        $chars = "abcdefghijklmnopqrstuvwxyz._ABCDEFGHIJKLNOPQRSTUVWXYZ-1234567890";
        $len = strlen($chars);
        for ($i = 0; $i < $length; $i++)
        {
            $x = rand(0, $len-1);
            $resstring .= substr($chars, $x, 1);
        }
        return $resstring;
    }

    final protected function randomSelection(string $source, $length=10) {
        if (strlen($source) <= $length) {
            return $source;
        }

        $len = strlen($source) - $length;
        $x = rand(0, $len - 1);
        return substr($source, $x, $length);
    }
}

?>
