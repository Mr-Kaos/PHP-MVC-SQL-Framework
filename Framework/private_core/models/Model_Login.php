<?php

namespace EasyMVC\Model;

require_once("Model.php");

/**
 * The Model object for the Login page.
 * This class handles the authentication of a user based on their login.
 * Its other MVC components are:
 * - View: Login.php
 * - Controller: {@see \EasyMVC\Controller\Controller_Login}
 *
 * When {@see Model_Login::fetchModelData()} is called (page load), it retrieves the table structure of the address table to allow the controller to build a form for it.
 * When {@see Model_Login::sendModelData()} is called, it either creates a new address record or updates an existing one. See {@see Model_Login::sendModelData()} for specifics.
 */
class Model_Login extends Model
{
	/** The default database table this model uses. */
	const DEFAULT_TABLE = "Logins";

	/**
	 * Constructor for Login model.
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(string $mode, array $data = null)
	{
		parent::__construct("Account","Logins", $mode);
	}

	/**
	 * Fetches data from the database to update the model.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 * 
	 * This override retrieves the structure of usp to create the form for creating a new address.
	 */
	public function fetchModelData(array $request = null): array
	{
		return [];
	}

	/**
	 * Submits the given data to the database via the stored procedures below and returns an navigational request for use in a location header.
	 * 
	 * The model can send data to the database from three methods:
	 * - Select:
	 * 	Checks to see if the given credentials exist in the database.
	 */
	public function sendModelData(array $data, string $submitMode = null): array | null
	{
		$response = null;

		switch ($submitMode) {
			case MODE_SELECT:
				$response = $this->executeStoredProcedure("Account", "usp_sel_UserLogin", $data);
				break;
		}

		return $response;
	}
}
