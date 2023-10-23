<?php

namespace Application\SessionManagement;

/**
 * Authenticate.php.inc
 * NOTICE: THIS FILE MUST BE INCLUDED ON EVERY PAGE IN THE WEB APPLICATION! 
 * 
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
	private const EXPIRE_TIME_LIMIT = 60; // The time limit for if a user is inactive for too long.
	private const SESSION_TOKEN_EXPIRY = 15; // The time limit from the start of a session where a session token will expire and be replaced with a new one. Session Hijacking prevention.
	private const ACCESS_CONTROL_LIST = 'acl.json';

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
	 */
	public function validateRequest(string $page)
	{
		$valid = false;
		$userGroup = "None";

		if ($this->checkTimeout()) {
			session_unset();
			session_destroy();
		} elseif (isset($_SESSION[USER_INFO])) {
			$userGroup = $_SESSION[USER_INFO]['Group'];
		}

		$valid = $this->checkPageFromFile($page, $userGroup);

		return $valid;
	}

	/**
	 * Checks the last request that made in the current session. If the request was over the specified time, in {@see EXPIRE_TIME_LIMIT}, the session will reset, logging the user out.
	 * @return bool False if the session has not timed out. True if session has timed out.
	 */
	private function checkTimeout(): bool
	{
		$timedOut = false;

		//Last Activity Check
		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] >  $this::EXPIRE_TIME_LIMIT * 60)) {
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
	 * Checks that the user is authenticated in the site using an access-control-list file.
	 */
	private function checkPageFromFile(string $url, string $userGroup): bool
	{
		$authenticated = false;

		$split = explode('/', $url);
		$view = $split[0];
		$page = isset($split[1]) ?  $split[1] : 'default';

		$json = json_decode(file_get_contents($this::ACCESS_CONTROL_LIST, true), true);
		$validViews = $json['UserGroups'][$userGroup];

		if ($validViews == '*') {
			$authenticated = true;
		} else {
			// check if the view is listed as accessible
			if (isset($validViews[$view])) {
				// if view is accessible, check that the page requested is also accessible
				if ($validViews[$view] == '*') {
					$authenticated = true;
				} elseif (is_array($validViews[$view])) {
					foreach ($validViews[$view] as $pg) {
						if ($page == $pg) {
							$authenticated = true;
							break;
						}
					}
				}
			}
		}

		return $authenticated;
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
