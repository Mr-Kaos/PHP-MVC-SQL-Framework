<?php

namespace Application\Model;

require_once("Model.php");

/**
 * The Model object for the Sample page.
 * This class is a demo on how to use a model object with a table in the connected database.
 * Its other MVC components are:
 * - View: Sample.php
 * - Controller: {@see \Application\Controller\Controller_Sample}
 *
 * When {@see Model_Sample::fetchModelData()} is called (on page load), it retrieves the respective data for the page the client is on and returns it to the controller.
 * When {@see Model_Sample::sendModelData()} is called, it would typically send data to the database. However, with the table used in this sample, that is not possible. See {@see Model_Sample::sendModelData()} for specifics.
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
		parent::__construct('INFORMATION_SCHEMA', 'SCHEMATA', $mode);
		$this->setTableIdentifiers("Sample", 'User', 'vw_Sample', 'window.location=\'Sample/edit?User=$id\'', ["id" => "UserID"]);
	}

	/**
	 * Fetches data from the database to update the model.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 * 
	 * This override retrieves the structure of usp1101 to create the form for creating a new customer.
	 */
	public function fetchModelData(array $request = null, string $submitMode = null): array
	{
		$modelData = array();
		$modelData["Name"] = $this->getDBObjectName();

		if ($this->getDBConnection()) {
			switch ($this->getMode()) {
				case "otherPage":
					$modelData[DATA_FORM_EDIT] = $this->queryDatabaseObject($this->dbSchema, $this->dbObject, null, "WHERE SCHEMA_NAME LIKE 'db_%'");
				default:
					break;
			}
		}

		return $modelData;
	}

	/**
	 * Submits the given data to the database via the stored procedures below and returns an navigational request for use in a location header.
	 * 
	 * The model can send data to the database from three methods:
	 * - Create:
	 * 	If creating a new record, all data related to an address except an SampleId should be given. Executes usp_name.
	 * - Update:
	 *	If updating an existing address record, an SampleId should be given.
	 * - Delete:
	 * 	If deleting an existing record, an SampleId and "Delete" value should be given.
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
