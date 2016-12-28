<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
namespace PowerTLA\Service\Ilias;

require_once('Services/Init/classes/class.ilInitialisation.php');

/**
* ILIAS Initialisation Utility Class
* perform basic setup: init database handler, load configuration file,
* init user authentification & error handler, load object type definitions
*
* @version $Id: class.ilInitialisation.php 52681 2014-08-27 08:21:21Z jluetzen $
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
\ilContext::init(\ilContext::CONTEXT_REST);

class Init_4_4 extends ilInitialisation
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

    //INHERIT: php5 compliance
    //INHERIT: initIliasIniFile
    //INHERIT: buildHTTPPath
    //INHERIT: determineClient
    //INHERIT: initDatabase
    //INHERIT: setSessionHandler
    //INHERIT: initSettings
    //INHERIT: initGlobal
    //INHERIT: handleDevMode

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
	 * ilias initialisation
	 */
	public static function initILIAS()
	{
		global $tree;

		self::initCore();

		if(\ilContext::initClient())
		{
			self::initClient();

			if (\ilContext::hasUser())
			{
				self::initUser();
				self::authenticate();
			}

			// init after Auth otherwise breaks CAS
			self::includePhp5Compliance();

			// language may depend on user setting
            // Object selection breaks if the laguage is not set.
			self::initLanguage();
			$tree->initLangCode();
		}
	}

    public static function goToPublicSection($a_auth_stat = ""){}


    // FIXME: Set a Flag for the services to respond accordingly
    protected static function handleMaintenanceMode(){}

	/**
	 * set session cookie params for path, domain, etc.
	 *
	 * FIXME: If a users accesses the services from the web,
	 *        there might be a cookie set, the services are interested in
	 */
	protected static function setCookieParams(){}

	protected static function initStyle(){}
    protected static function initLocale(){}
	protected static function goToLogin($a_auth_stat = ""){}
    protected static function handleAuthenticationSuccess(){}
    protected static function handleAuthenticationFail(){}
    protected static function initHTML(){}
    protected static function getCurrentCmd(){}
	protected static function redirect($a_target, $a_message_id, $a_message_static){}
    protected static function handleDevMode(){}

	protected static function blockedAuthentication($a_current_script)
	{
		return true; // due to service level authentication
	}

    protected static function showingLoginForm($a_current_script)
	{
		return false;
	}

}

?>
