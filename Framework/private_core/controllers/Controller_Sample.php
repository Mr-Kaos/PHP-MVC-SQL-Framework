<?php

namespace Application\Controller;

use Application\Model as m;
use Application\PageBuilder as pb;

require_once("Controller.php");
require_once("private_core/objects/PageObjects/Modal.php");
require_once("private_core/objects/PageBuilder/FormBuilder.php");

/**
 * The Controller object for the Sample page.
 * This class handles the data being sent and received from the server for the Sample page.
 * Its other MVC components are:
 * - View: Sample
 * - Model: Model_Sample.php
 *  
 * When receiving data from the model, it creates page elements to be used in the view. In this case, a form using the FormBuilder object.
 * When sending data to the model (from a form submission), it validates the data and sends it to the model via sendModelData() for
 * insertion into the database.
 */
class Controller_Sample extends Controller
{
	/**
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(array $data = null)
	{
		$mode = isset($data["mode"]) ? $data["mode"] : null;
		parent::__construct(null, $mode);
	}

	/**
	 * Validates and sends user-submitted data to the model for submission in the database. 
	 * @param array $data An associative array of the data to be validated and sent to the model. Typically an array obtained from a POST request.
	 * @return mixed The response from the model after the validated data is sent to it.
	 */
	public function postData(array $data, string $mode): mixed
	{
		$data = $this->validateDataParameters($data, $mode);
		if (gettype($data) == 'array') {

		} else {
			$_SESSION["MSG_ERROR"] = $data;
			$destination = $_SERVER["HTTP_REFERER"];
		}

		return $destination;
	}

	/** Retrieves the data from the Database and conforms it to the overridden function's specifications. 
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public function retrieveData(array $request = null): void
	{
		switch ($this->getPage()) {
			case 'otherPage':
				$fb = new pb\FormBuilder("", null, "");
				break;
			default:
		}
	}

	/** 
	 * Validates customer form submission to suit the stored procedure for the respective mode.
	 */
	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		$validatedData = array();

		switch ($mode) {
			case m\MODE_INSERT:
				$validatedData["Username"] = $this->validatePostInput($data["username"]);
				if ($data["passwordA"] === $data["passwordB"]) {
					$validatedData["Password"] = $this->validatePostInput($data["passwordA"]);
				} else {
					return 'Passwords do not match.';
				}
				$validatedData["UserGroup"] = $this->validatePostInput($data["userGroup"]);;
				$validatedData["FirstName"] = $this->validatePostInput($data["fName"]);
				$validatedData["LastName"] = $this->validatePostInput($data["lName"]);
				$validatedData["AcademicLevel"] = $this->validatePostInput($data["academicLevel"]);
				$validatedData["Email"] = $this->validatePostInput($data["email"]);
				$validatedData["PhoneNumber"] = $this->validatePostInput($data["phone"]);
				break;
			case m\MODE_UPDATE:
				break;
			case m\MODE_DELETE:
				$validatedData["UserId"] = $data["delete"];
				break;
		}

		return $validatedData;
	}
}
