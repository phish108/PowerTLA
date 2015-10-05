<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('Services/Init/classes/class.ilInitialisation.php');

/**
* ILIAS Initialisation Utility Class
* perform basic setup: init database handler, load configuration file,
* init user authentification & error handler, load object type definitions
*
* @author Alex Killing <alex.killing@gmx.de>
* @author Sascha Hofmann <shofmann@databay.de>
*
* @author Christian Glahn
*
* @version $Id$
*
* @ingroup ServicesInit
*/

/**
 * IMPORTANT FOR NEW VERSIONS!
 *
 * Because this class is only used for the TLA REST services, it needs to
 * run in the ilContext::CONTEXT_REST mode to avoid redirects etc.
 *
 * ilContext::CONTEXT_REST is available since ilias 4.3!
 */
include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

class ilRESTInitialisation extends ilInitialisation
{
	/**
	 * get common include code files
	 */
	protected static function requireCommonIncludes()
	{
		// pear
		require_once("include/inc.get_pear.php");
		require_once("include/inc.check_pear.php");
		require_once "PEAR.php";

		// really always required?
		require_once "./Services/Utilities/classes/class.ilUtil.php";
		require_once "./Services/Utilities/classes/class.ilFormat.php";
		require_once "./Services/Calendar/classes/class.ilDatePresentation.php";
		require_once "include/inc.ilias_version.php";

		self::initGlobal("ilBench", "ilBenchmark", "./Services/Utilities/classes/class.ilBenchmark.php");
	}

	/**
	 * handle maintenance mode
	 */
	protected static function handleMaintenanceMode()
	{}

	/**
	 * set session cookie params for path, domain, etc.
	 */
	// protected static function setCookieParams()
	// {}

	/**
	 * provide $styleDefinition object
	 */
	protected static function initStyle()
	{}

	/**
	 * Init Locale
	 */
	protected static function initLocale()
	{}

	/**
	 * go to public section
	 *
	 * @param int $a_auth_stat
	 */
	public static function goToPublicSection($a_auth_stat = "")
	{}

	/**
	 * go to login
	 *
	 * @param int $a_auth_stat
	 */
	protected static function goToLogin($a_auth_stat = "")
	{}

	/**
	 * Exit
	 *
	 * @param string $a_message
	 */
	protected static function abortAndDie($a_message)
	{
		if(is_object($GLOBALS['ilLog']))
		{
			$GLOBALS['ilLog']->write("Fatal Error: ilInitialisation - ".$a_message);
		}
        // DONT DIE, let the service generate a proper response!
	}

	/**
	 * Prepare developer tools
	 */
	protected static function handleDevMode()
	{}

	/**
	 * ilias initialisation
	 *
	 * This needs to be overwritten because Ilias would ignore several settings
	 * initIlias has to be overwritten because there is no easy way to
	 * refine the context definition
	 */
	public static function initILIAS()
	{
		if (self::$already_initialized)
		{
			return;
		}

		self::$already_initialized = true;

		global $tree;

		self::initCore();

		if(ilContext::initClient())
		{
			self::initClient();

			self::initUser();
			// NOTE authenticate() will setup the session user
			self::authenticate();

			// init after Auth otherwise breaks CAS
			self::includePhp5Compliance();

			// language may depend on user setting
			self::initLanguage();
			$tree->initLangCode();
		}
	}

	public static function authenticate()
	{
		global $ilAuth, $ilias, $ilErr, $ilUser;

		$oldSid = session_id();

		// error_log(">> init authenticate " . $oldSid);

		$ilAuth->start();
		$ilias->setAuthError($ilErr->getLastError());

		if($ilAuth->getAuth() && $ilAuth->getStatus() == '')
		{
			error_log(">> init authenticate no session?");
			if (ilSession::get("AccountId"))
         	{
            	// ensure that web users have access to the services
                self::initUserAccount();
         	}
		// 	self::initUserAccount();
		//
		// 	self::handleAuthenticationSuccess();
		}
		//
		// error_log(">> init authenticate user is: " . $ilUser->getId());
	}

	protected static function setCookieParams()
	{
		global $ilSetting;

		include_once 'Services/Authentication/classes/class.ilAuthFactory.php';

		$cookie_path = '/';

		/* if ilias is called directly within the docroot $cookie_path
		is set to '/' expecting on servers running under windows..
		here it is set to '\'.
		in both cases a further '/' won't be appended due to the following regex
		*/
		$cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";

		if($cookie_path == "\\") $cookie_path = '/';

		include_once './Services/Http/classes/class.ilHTTPS.php';
		$cookie_secure = !$ilSetting->get('https', 0) && ilHTTPS::getInstance()->isDetected();

		define('IL_COOKIE_EXPIRE',0);
		define('IL_COOKIE_PATH',$cookie_path);
		define('IL_COOKIE_DOMAIN','');
		define('IL_COOKIE_SECURE', $cookie_secure); // Default Value

		// session_set_cookie_params() supports 5th parameter
		// only for php version 5.2.0 and above
		if( version_compare(PHP_VERSION, '5.2.0', '>=') )
		{
			// PHP version >= 5.2.0
			define('IL_COOKIE_HTTPONLY',true); // Default Value
			session_set_cookie_params(
			IL_COOKIE_EXPIRE, IL_COOKIE_PATH, IL_COOKIE_DOMAIN, IL_COOKIE_SECURE, IL_COOKIE_HTTPONLY
		);
		}
		else
		{
				// PHP version < 5.2.0
				session_set_cookie_params(
				IL_COOKIE_EXPIRE, IL_COOKIE_PATH, IL_COOKIE_DOMAIN, IL_COOKIE_SECURE
				);
		}
	}


	/**
	 * @static
	 */
	// protected static function handleAuthenticationSuccess()
	// {}

	/**
	 * @static
	 */
	protected static function handleAuthenticationFail()
	{}

	/**
	 * init HTML output (level 3)
	 */
	protected static function initHTML()
	{}

	/**
	 * Extract current cmd from request
	 *
	 * @return string
	 */
	protected static function getCurrentCmd()
	{}

	/**
	 * Block authentication based on current request
	 *
	 * @return boolean
	 */
	protected static function blockedAuthentication($a_current_script)
	{
       return true;
   }

	/**
	 * Is current view the login form?
	 *
	 * @return boolean
	 */
	protected static function showingLoginForm($a_current_script)
	{
		return false;
	}



	/**
	 * Redirects to target url if context supports it
	 *
	 * @param string $a_target
	 * @param string $a_message_id
	 * @param array $a_message_details
	 */
	protected static function redirect($a_target, $a_message_id, $a_message_static)
	{}
}

?>
