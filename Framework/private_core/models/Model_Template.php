<?php

namespace Application\Model;

require_once("Model.php");

/**
 * The Model object for the <INSERT PAGE NAME HERE> page.
 * <Describe what the model is used for here>
 * 
 * Its other MVC components are:
 * - View: <Specify associated view here>
 * - Model: <Specify associated controller here>
 *
 * When {@see Model_Sample::fetchModelData()} is called (on GET request), it retrieves the respective data for the page the client requested and returns it to the controller.
 * When {@see Model_Sample::sendModelData()} is called (on POST request), the data sent is validated and sent to the database. Upon completion it directs the user to a URI specified.
 */
class Model_Sample extends Model
{
	/**
	 * Constructor for Sample model.
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 * @return void
	 */
	public function __construct(string $mode, array $data = null)
	{
		// Specify the base schema and table for this model.
		parent::__construct('INFORMATION_SCHEMA', 'SCHEMATA', $mode);

		/** If the views need a table iFrame to display records from a database table in, specify them using {@see Model::setTableIdentifiers()} */
		$this->setTableIdentifiers("Template", 'INFORMATION_SCHEMA', 'SCHEMATA');
	}

	/**
	 * Fetches data from the database to update the model.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 */
	public function fetchModelData(array $request = null, string $submitMode = null): array
	{
		$modelData = array();

		if ($this->getDBConnection()) {
			switch ($this->getMode()) {
					// Add cases for each page in the view that requires specific data to be retrieved for it.
				default:
					// logic specific to "default" page goes here
					break;
			}
		}

		return $modelData;
	}

	/**
	 * Submits the given data to the database via the stored procedures below and returns an navigational request for use in a location header.
	 * 
	 * The model can send data to the database from these modes:
	 * - Insert:
	 * 	<Add any notes regarding the insert mode>
	 * - Update:
	 * 	<Add any notes regarding the update mode>
	 * - Delete:
	 * 	<Add any notes regarding the delete mode>
	 * - <Add any other modes here>
	 */
	public function sendModelData(array $data, string $submitMode = null): array | null
	{
		$response = null;
		switch ($submitMode) {
			case MODE_INSERT:
				$response = $this->executeStoredProcedure("dbo", "usp_name", $data);
				break;
			case MODE_UPDATE:
				$response = $this->executeStoredProcedure("dbo", "usp_name", $data);
				break;
			case MODE_DELETE:
				$response = $this->executeStoredProcedure("dbo", "usp_name", $data);
				break;
		}

		if (isset($response["FAILURE"])) {
			$_SESSION["MSG_ERROR"] = $response["FAILURE"];
		}

		return $response;
	}
}
