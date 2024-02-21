<?php

namespace EasyMVC\Controller;

use EasyMVC\Model\Model_Home;

require_once("Controller.php");
require_once("private_core/models/Model_Home.php");
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
class Controller_Home extends Controller
{
	/**
	 * Takes an associated array (typically a GET request) and determines what data needs to be retrieved from the model.
	 * Constructs the model based on the given data. Provides the model with the table/view to retrieve data from.
	 * @param array $data An associative array that contains data for the controller to create a model with. Usually $_GET.
	 */
	public function __construct(array $data = null)
	{
		$mode = isset($data["mode"]) ? $data["mode"] : null;
		parent::__construct(new Model_Home($mode, $data), $mode);
	}

	/**
	 * Validates and sends user-submitted data to the model for submission in the database. 
	 * @param array $data An associative array of the data to be validated and sent to the model. Typically an array obtained from a POST request.
	 * @return string The destination URI to direct to upon completion.
	 */
	public function postData(array $data, string $mode): mixed
	{
		return null;
	}

	/**
	 * Prepares data for use in the view's pages.
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public function retrieveData(array $request = null): void
	{
		$modelData = $this->getModel()->fetchModelData();

		switch ($this->getPage()) {
			default:
				if (constant('REQUIRE_DB')) {
					$dbInfo = '<p><b>Server address:</b> ' . constant('DB_SERVER') . '</br><b>Database:</b> ' . constant('DB_NAME') . '</p>';
					if ($modelData['Conn']) {
						$dbInfo .= '<p><b>Connection to database successful!</b></p>';
					} else {
						$dbInfo .= '<p><b>Failed to connect to database.</b><br>Are the credentials and details correct?</p>';

					}
					$this->setPreparedData("DB", $dbInfo);
				}
				break;
		}
	}

	/** 
	 * Not used in Home pages as there are no forms or uses for data manipulation.
	 */
	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		return null;
	}
}
