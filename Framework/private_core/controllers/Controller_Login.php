<?php

namespace EasyMVC\Controller;

use EasyMVC\Model as m;
use EasyMVC\SessionManagement as s;
use EasyMVC\PageBuilder as pb;

use const EasyMVC\SessionManagement\LOGIN_REDIRECT;
use const EasyMVC\SessionManagement\USER_INFO;

require_once("private_core/models/Model_Login.php");
require_once("private_core/objects/PageBuilder/FormBuilder.php");
require_once("private_core/objects/PageObjects/Modal.php");
require_once("private_core/objects/SessionManager.php");

/**
 * The Controller object for the Address page.
 * This class handles the data being sent and received from the server for the Address page.
 * Its other MVC components are:
 * - View: Address.php
 * - Model: Model_Address.php
 *  
 * When receiving data from the model, it creates page elements to be used in the view. In this case, a form using the FormBuilder object.
 * When sending data to the model (from a form submission), it validates the data and sends it to the model via sendModelData() for
 * insertion into the database.
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
		// If the page is loaded with the "logout" GET variable, end the user's session.
		if (isset($data['logout'])) {
			$this->logOut();
			die();
		}
		$mode = isset($data["mode"]) ? $data["mode"] : null;
		$data = array("Account" => 'Logins');
		parent::__construct(new \EasyMVC\Model\Model_Login($mode, $data), $mode);
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
		$destination = 'Login';

		if (!isset($response["FAILURE"])) {
			switch ($mode) {
				case m\MODE_SELECT:
					$response = $response;
					if (isset($response["LoginId"])) {
						if ($this->logUserIn($response)) {
							$destination = $_SESSION["LOGIN_REDIRECT_DATA"]["TARGET"];
							$getValues = $_SESSION["LOGIN_REDIRECT_DATA"]["TEMP_GET"];
							$getValueHTTP = "";
							foreach ($getValues as $key => $val) {
								if ($getValueHTTP == "") {
									$getValueHTTP .= "?";
								} else {
									$getValueHTTP .= "&";
								}
								$getValueHTTP .= $key . "=" . $val;
							}
							$destination .= $getValueHTTP;
							if( strtolower(substr($destination,0,5)  )=="login" ){
								$destination = "Home";
							}
						} else {
							$_SESSION["LOGIN_ERROR"] = 'Failed to authenticate login.';
						}
					}
					break;
			}
		} else {
			$_SESSION["LOGIN_ERROR"] = $response["FAILURE"];
		}
		return $destination;
	}

	/** Retrieves the data from the Database and conforms it to the overridden function's specifications. 
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public function retrieveData(array $request = null): void
	{
		switch ($this->getPage()) {
			case 'default':
				if (isset($_SESSION["LOGIN_ERROR"])) {
					$this->setPreparedData("err", "<div class='alert-box-warning'>" . $_SESSION["LOGIN_ERROR"] . "</div>");
					unset($_SESSION["LOGIN_ERROR"]);
				}
				break;
		}
	}

	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		$validated = array();
		switch ($mode) {
			case m\MODE_SELECT:
				$validated = $data;
				break;
		}
		return $validated;
	}

	private function logUserIn($loginDetails): bool
	{
		$loginData = array();
		$success = false;
		if (isset($loginDetails["LoginId"]) && isset($loginDetails["Username"])) {
			if (is_numeric($loginDetails["LoginId"])) {
				$loginData["LoginId"] = $loginDetails["LoginId"];
				$loginData["UserId"] = $loginDetails["ContactEmployeeId"];
				$loginData["IsEmployee"] = $loginDetails["IsEmployeeLogin"];
				$loginData["Username"] = $loginDetails["Username"];
				$loginData["PrivilegeLevel"] = $loginDetails["PrivilegeLevel"];
				$loginData["Group"] = $loginDetails["UserGroup"];
				$_SESSION[USER_INFO] = $loginData;
				$success = true;
				$_SESSION['MSG_STATUS'] = 'Successfully Logged in';
			}
		}

		return $success;
	}

	private function logOut()
	{
		$session = new s\SessionManager();
		$session->endSession();
		$_SESSION['MSG_STATUS'] = 'Successfully logged out.';
		header('location:Login');
	}
}
