<?php

namespace Application\Controller;

use Application\Model as m;
use Application\SessionManagement as s;

require_once("Controller.php");
require_once("private_core/models/Model_Login.php");
require_once("private_core/objects/SessionManager.php");
require_once("private_core/objects/PageObjects/Modal.php");
require_once("private_core/objects/PageBuilder/FormBuilder.php");

/**
 * The Controller object for the Login.
 * This class handles the data being sent and received from the server for the Login page.
 */
class Controller_Login extends Controller
{
	/**
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(array $data = null)
	{
		$mode = isset($data["mode"]) ? $data["mode"] : null;
		parent::__construct(new m\Model_Login($mode, $data), $mode);
	}

	/**
	 * Validates and sends user-submitted data to the model for submission in the database. 
	 * @param array $data An associative array of the data to be validated and sent to the model. Typically an array obtained from a POST request.
	 * @return mixed The response from the model after the validated data is sent to it.
	 */
	public function postData(array $data, string $mode): mixed
	{
		$data = $this->validateDataParameters($data, $mode);
		$response = $this->getModel()->sendModelData($data, $mode);

		$destination = isset($_SESSION[s\LOGIN_REDIRECT]) ? $_SESSION[s\LOGIN_REDIRECT] : 'Login';

		if (!isset($response["FAILURE"])) {
			switch ($mode) {
				case m\MODE_SELECT:
					if (isset($response["LoginID"])) {
						if ($this->logIn($response)) {
							$destination = "Home";
						} else {
							$_SESSION["MSG_ERROR"] = 'Failed to authenticate login.';
						}
					} else {
						$destination = 'Login';
					}
					break;
			}
		} else {
			$_SESSION["MSG_ERROR"] = $response["FAILURE"];
		}

		return $destination;
	}

	/** Retrieves the data from the Database and conforms it to the overridden function's specifications. 
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public function retrieveData(array $request = null): void
	{
		// Log user out if the GET variable 'logout' is given
		if (isset($request['logout'])) {
			$this->logOut();
			die();
		}
	}

	/** 
	 * Validates form data
	 */
	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		$validatedData = array();

		switch ($mode) {
			case m\MODE_SELECT:
				$validatedData["Username"] = $this->validatePostInput($data["username"]);
				$validatedData["Password"] = $this->validatePostInput($data["password"]);
				break;
		}

		return $validatedData;
	}

	private function logIn($loginDetails): bool
	{
		$loginData = array();
		$success = false;
		if (isset($loginDetails["LoginID"]) && isset($loginDetails["UserID"])) {
			if (is_numeric($loginDetails["LoginID"])) {
				$loginData["LoginId"] = $loginDetails["LoginID"];
				$loginData["UserId"] = $loginDetails["UserID"];
				$loginData["PrivilegeLevel"] = $loginDetails["PrivilegeLevel"];
				$loginData["Group"] = $loginDetails["UserGroup"];
				$_SESSION['USER_INFO'] = $loginData;
				$success = true;
			}
		}

		return $success;
	}

	private function logOut()
	{
		new s\SessionManager();
		unset($_SESSION[s\USER_INFO]);
		$_SESSION['MSG_STATUS'] = 'Successfully logged out.';
		header('location:Login');
	}
}
