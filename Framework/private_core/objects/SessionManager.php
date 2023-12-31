<?php

namespace EasyMVC\SessionManagement;

/**
 * This script handles user authentication to the server.
 * It will initialise a session if one is has not already been created and will check if the client's
 * session variables are valid or not when accessing a web page.
 * 
 * DEBUGGING NOTE: If sessions are ending too early, check the php.ini file for the session.gc_maxlifetime value, and ensure it is set to a value greater (in seconds) than the value of the EXPIRE_TIME_LIMIT constant.
 * This is because the garbage collector may be deleting the session files before the session is supposed to end. 
 */

const LOGIN_REDIRECT = 'LOGIN_REDIRECT';
const USER_INFO = 'USER_INFO';

class SessionManager
{
	//Both Values below are in minutes.
	private const SESSION_TOKEN_EXPIRY = 15; // The time limit from the start of a session where a session token will expire and be replaced with a new one. Session Hijacking prevention.
	private const ACCESS_CONTROL_LIST = 'acl.json';
	private const EXCLUDED_PAGES = ['Login', 'Table'];

	/**
	 * Constructor for session manager. Starts the session.
	 */
	function __construct()
	{
		$this->startSession();
	}

	/**
	 * Checks if the request in the session session is valid.
	 * First checks if the session has timed out. Then check if the user is logged in and is accessing a page they are allowed to visit.
	 * @return bool True if the request is valid. False if it is not.
	 */
	public function validateRequest(string $page)
	{
		$valid = false;
		$userGroup = 5;

		if ($this->checkTimeout()) {
			session_unset();
			session_destroy();
		} elseif (isset($_SESSION[USER_INFO])) {
			$userGroup = $_SESSION[USER_INFO]['Group'];
			$valid = true;
		}

		if (constant('AUTH_METHOD') == 'file') {
			$valid = $this->checkPageFromFile($page, $userGroup);
		} else {
			$valid = $this->checkPageFromDatabase($page, $userGroup);
		}

		return $valid;
	}

	/**
	 * Checks if the user is authenticated into the site.
	 */
	public function checkAuthentication(): bool
	{
		$authenticated = false;

		if (isset($_SESSION['USER_INFO'])) {
			$authenticated = true;

			// Add checks to ensure the login details are up to date. I.e., if the username was changed and they are still logged in, update the SESSION data.
		}

		return $authenticated;
	}

	/**
	 * Checks the last request that made in the current session. If the request was over the specified time, in {@see EXPIRE_TIME_LIMIT}, the session will reset, logging the user out.
	 * @return bool False if the session has not timed out. True if session has timed out.
	 */
	private function checkTimeout(): bool
	{
		$timedOut = false;

		//Last Activity Check
		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] >  constant('TIMEOUT_LOGIN') * 60)) {
			$timedOut = true;
		}
		$_SESSION['LAST_ACTIVITY'] = time();

		//Session Token Regeneration
		if (!isset($_SESSION['CREATED'])) {
			$_SESSION['CREATED'] = time();
		} else if (time() - $_SESSION['CREATED'] > $this::SESSION_TOKEN_EXPIRY * 60) {
			session_regenerate_id(true);
			$_SESSION['CREATED'] = time();
		}

		return $timedOut;
	}

	/**
	 * Checks that the user is granted access to the requested page using data stored in the database.
	 */
	private function checkPageFromDatabase(string $url, string $userGroup): bool
	{
		$accessGranted = false;
		$qry = 'SELECT AccessControlJSON FROM [WebApp].[GroupPageAccessControl] WHERE UserGroup = ?';

		$connectionInfo = array("Database" => constant('DB_NAME'), "UID" => constant('DB_USERNAME'), "PWD" => constant('DB_PASSWORD'), "CharacterSet" => "UTF-8");
		$conn = sqlsrv_connect(constant('DB_SERVER'), $connectionInfo);
		if (!$conn) {
			$_SESSION["MSG_ERROR"] = 'Unable to connect to the database.';
		}

		$result = sqlsrv_query($conn, $qry, [$userGroup]);
		if ($result) {
			$result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)['AccessControlJSON'];
			if (!is_null($result)) {
				if (!is_null($json = json_decode($result, true))) {
					$accessGranted = $this->checkPageAccessJSON($json, $url);
				} else {
					echo 'debug: failed to parse json';
				}
			}
		} else {
			echo 'debug: failed to retrieve access control list';
		}

		return $accessGranted;
	}

	/**
	 * Checks that the user is granted access to the requested page using an access-control-list file.
	 */
	private function checkPageFromFile(string $url, string $userGroup): bool
	{
		$accessGranted = false;

		$json = json_decode(file_get_contents($this::ACCESS_CONTROL_LIST, true), true);
		$validViews = $json['UserGroups'][$userGroup];
		$this->checkPageAccessJSON($validViews, $url);

		return $accessGranted;
	}

	/**
	 * Parses a JSON object containing page access control for the given user/user group.
	 * Contains exclusions for some views, such as Login and Table views.
	 * @param array $json An array of the decoded JSON object containing the Views and pages that are accessible to the specified user group.
	 * @param string $url The URL of the requested page to be accessed.
	 */
	private function checkPageAccessJSON(array $json, string $url)
	{
		$accessGranted = false;

		$split = explode('/', $url);
		$view = $split[0];
		$page = isset($split[1]) ?  $split[1] : 'default';

		if (in_array($view, $this::EXCLUDED_PAGES)) {
			$accessGranted = true;
		} elseif (array_key_exists("*", $json)) {
			$accessGranted = true;
		} else {
			// check if the view is listed as accessible
			if (isset($json[$view])) {
				// if view is accessible, check that the page requested is also accessible
				if ($json[$view] == '*') {
					$accessGranted = true;
				} elseif (is_array($json[$view])) {
					foreach ($json[$view] as $pg) {
						if ($page == $pg) {
							$accessGranted = true;
							break;
						}
					}
				}
			}
		}

		return $accessGranted;
	}

	private function startSession()
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}

	public function endSession()
	{
		$this->startSession();
		session_unset();
		session_destroy();
	}
}
