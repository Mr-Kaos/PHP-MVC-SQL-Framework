<?php

/**
 * Controller.php
 * 
 * Defines the Controller Base class and constants used throughout controllers and related objects.
 */

namespace Application\Controller;

use \Application\Model\Model;
require_once("private_core/controllers/DataValidators.php");

/** Signifies that the form data validation and submission was successful.  */
const STATUS_SUCCESS = 0;
/** Signifies that the form data validation was unsuccessful.  */
const STATUS_FAILURE = 1;
/** Signifies that the form data validation and submission was unsuccessful.  */
const STATUS_SUBMIT_FAILURE = 2;
/** Constant used to signify the "New" option in a dropdown box. */
const DROPDOWN_NEW_OPTION = "modal_new";

/**
 * Base Controller class
 * 
 * Each controller is paired to a model object.
 * 
 * Each controller has an array that stores the data that is prepared for use in the view.
 * This data should be retrieved from its paired view using the getPreparedData() function.
 * 
 * This class contains methods used to help the validation, retrieval and formatting of data that is to be sent and received from its paired model.
 * 
 * All data is validated here (or in any implementations of abstract methods) before being sent to the model for storing.
 * Similarly, data that is retrieved from the model is validated and sanitised before being displayed to the user (if necessary).
 * 
 * @property Model $model The Model associated with this controller. The controller feeds data directly into the model.
 * @property array $preparedData An associative array of the data extracted from the model and validated by the controller.
 * @property bool $submitToDatabase Determines if the controller is being used to insert data to the database.
 */
abstract class Controller
{
	use  \Application\Controller\DataValidator;
	private ?Model $model;
	private array $preparedData;
	private bool $submitToDatabase;
	private bool $abortSubmit = false;
	private string $pageMode;

	/**
	 * Base Controller Constructor.
	 * Different to the constructors in all controller implementations as it initialises the base controller properties and associates a model object.
	 * 
	 * The implementations of the controllers set the database tables for its model and page-specific data that the paired view will display.
	 */
	public function __construct(?Model $model, string $mode, bool $submitToDatabase = false)
	{
		$this->model = $model;
		$this->preparedData = array();
		$this->pageMode = $mode;
		$this->submitToDatabase = $submitToDatabase;
	}

	/**
	 * Destructs the controller object.
	 */
	public function __destruct()
	{
	}

	/**
	 * Validates and sends user-submitted data to the model for submission in the database. 
	 * @param array $data An associative array of the data to be validated and sent to the model. Typically an array obtained from a POST request.
	 * @return mixed The response from the model after the validated data is sent to it.
	 */
	public abstract function postData(array $data, string $mode): mixed;

	/**
	 * Retrieves the data from the Database and conforms it to the overridden function's specifications. 
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public abstract function retrieveData(array $request): void;

	/**
	 * Validates the data sent to it to conform with the model for submission into the database.
	 * @param array $data An array retrieved from a form submission containing the data to be validated.
	 * @param string $mode The mode in which the data needs to be validated for. See @see{Application\Model} for MODE constants.
	 * @return array An associative array of the validated data. If data is being sent to a stored procedure, each key must correspond to a parameter in the stored procedure.
	 * @return string If an error occurs in the validation, a string containing the error message may be returned. Should only be used if an error occurs.
	 * @return null If the controller is not used to validate any parameters, the function must always return null.
	 * */
	protected abstract function validateDataParameters(array $data, string $mode = 'default'): array | string | null;

	/**
	 * Returns the Model object associated to this controller.
	 * @return Model The controller's associated model.
	 */
	protected function getModel(): Model
	{
		return $this->model;
	}

	/**
	 * Returns the page the controller is being used in (i.e. create, insert, update, delete)
	 * @return string The page mode. The mode corresponds to the name of a page within a view.
	 */
	protected function getPage(): string
	{
		return $this->pageMode;
	}

	/**
	 * Returns data associated to the specified key.
	 * This should be called in views.
	 * @param string $key The array key in the preparedData array that contains the content to be echoed on the page.
	 * @return string If the requested data exists, the data is returned. Else, null is returned.
	 */
	public function getPreparedData(string $key): string | null
	{
		return isset($this->preparedData[$key]) ? $this->preparedData[$key] : null;
	}

	/**
	 * Checks if there is data available in the given key.
	 * @param string $key The array key in the preparedData array to check if it exists.
	 */
	public function checkPreparedData(string $key): bool
	{
		$isSet = false;
		if (isset($this->preparedData[$key])) {
			$isSet = true;
		}
		return $isSet;
	}

	/**
	 * Sets the given data to the preparedData array for output in the view.
	 * Given keys should be unique within this view. No two keys should be the same.
	 * 
	 * @param string $key The identifier that the assigned data will be assigned to.
	 * @param string $data The data in the form of a string to associate with the key.
	 */
	protected function setPreparedData(string $key, string $data): void
	{
		$this->preparedData[$key] = $data;
	}
}
