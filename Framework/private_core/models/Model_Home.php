<?php

namespace EasyMVC\Model;

require_once("Model.php");

/**
 * The Model object for the <INSERT PAGE NAME HERE> page.
 * <Describe what the model is used for here>
 * 
 * Its other MVC components are:
 * - View: Home
 * - Controller: {@see \EasyMVC\Controller\Controller_Home}
 *
 * When {@see Model_Home::fetchModelData()} is called (on GET request), it retrieves the respective data for the page the client requested and returns it to the controller.
 * When {@see Model_Home::sendModelData()} is called (on POST request), the data sent is validated and sent to the database. Upon completion it directs the user to a URI specified.
 */
class Model_Home extends Model
{
	/**
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(string $mode)
	{
		// Specify the base schema and table for this model.
		parent::__construct('INFORMATION_SCHEMA', 'SCHEMATA', $mode);
	}

	/**
	 * Fetches data from the database to update the model.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 */
	public function fetchModelData(?array $request = null, string $submitMode = null): array
	{
		$modelData = array();

		if ($this->getDBConnection()) {
			switch ($this->getPage()) {
				default:
					$modelData["Conn"] = true;
					break;
			}
		} elseif (constant('REQUIRE_DB')) {
			$modelData["Conn"] = false;
		}

		return $modelData;
	}

	/**
	 * Not used in Home view, returns null if called.
	 */
	public function sendModelData(array $data, string $submitMode = null): array | null
	{
		return null;
	}
}
