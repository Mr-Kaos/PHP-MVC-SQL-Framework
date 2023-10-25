<?php

namespace Application\Controller;

use Application\Model as m;
use Application\Model\Model_Template;

require_once("Controller.php");
require_once("private_core/models/Model_Template.php");
require_once("private_core/objects/PageObjects/Modal.php");

/**
 * The Controller object for the <INSERT PAGE NAME HERE> page.
 * <Describe what the controller is used for here>
 * 
 * Its other MVC components are:
 * - View: <Specify associated view here>
 * - Model: <Specify associated model here, if one is required>
 * 
 */
// Be sure to rename the Controller class to the name of the view!
class Controller_Template extends Controller
{
	/**
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(array $data = null)
	{
		$mode = isset($data["mode"]) ? $data["mode"] : null;
		parent::__construct(new Model_Template($mode, $data), $mode);
	}

	/**
	 * Validates and sends user-submitted data to the model for submission in the database. 
	 * @param array $data An associative array of the data to be validated and sent to the model. Typically an array obtained from a POST request.
	 * @return string The destination URI to direct to upon completion.
	 */
	public function postData(array $data, string $mode): mixed
	{
		$data = $this->validateDataParameters($data, $mode);
		$destination = 'Template';

		if (gettype($data) == 'array') {
			// Send the validated data to the model and receive its response
			$response = $this->getModel()->sendModelData($data, $mode);
			switch ($mode) {
				case m\MODE_INSERT:
					// Perform any necessary operations if the INSERT mode was successful.

					// Set a useful status message to inform the user the insert was successful.
					$_SESSION["MSG_STATUS"] = "Successfully inserted data";
					break;
				case m\MODE_UPDATE:
					// Perform any necessary operations if the UPDATE mode was successful and set a status message.
					$_SESSION["MSG_STATUS"] = "Successfully updated data";
					break;
				case m\MODE_DELETE:
					// Perform any necessary operations if the DELETE mode was successful and set a status message.
					$_SESSION["MSG_STATUS"] = "Successfully deleted data";
					break;
			}
		} else {
			// Set the error message to what the validation returned and redirect to the previous page (or a URI of your choice).
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
				// Add cases for each page in the view that requires its own unique logic.
			default:
				// logic specific to "default" page goes here
				break;
		}
	}

	/** 
	 * Validates customer form submission to suit the stored procedure for the respective mode.
	 */
	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		$validatedData = array();

		// Validate each piece of data in their respective switch case. Each validated value should be stored in its own array key.
		switch ($mode) {
			case m\MODE_INSERT:
				break;
			case m\MODE_UPDATE:
				break;
			case m\MODE_DELETE:
				break;
		}

		return $validatedData;
	}
}
