<?php

namespace EasyMVC\Controller;

use EasyMVC\Model as m;
use EasyMVC\PageBuilder as pb;

require_once("private_core/models/Model_Table.php");
require_once("private_core/objects/PageBuilder/FormBuilder.php");
require_once("private_core/objects/PageBuilder/TableBuilder.php");

/**
 * The Controller object for the Table page.
 * This controller is slightly different to other controllers as it retrieves its data from session variables set from other models.
 * Its other MVC components are:
 * - View: Table.php
 * - Model: Model_Table.php
 * 
 * This controller retrieves its data from its model, which obtains its data from session declared by other models. More information
 * can be found in the Model_Table.php documentation.
 *  
 * When receiving data from the model, it creates a basic HTML page with a single table element in it.
 * The controller never sends data back to its model. THe model is purely used to retrieve data.
 */
class Controller_Table extends Controller
{
	private ?string $tableDataName;

	/** Additional data is typically an array based on GET variables. */
	public function __construct(array $data)
	{
		$this->tableDataName = isset($data["TableDataName"]) ? $data["TableDataName"] : null;
		parent::__construct(new \EasyMVC\Model\Model_Table($data["TableDataName"]), false);
	}

	/**
	 * Since this controller does not send data to its model, no data submission is needed.
	 * This function only exists as it is an abstract function that needs to be implemented.
	 */
	public function postData(array $data, string $mode): mixed
	{
		$data = $this->validateDataParameters($data);

		$destination = "Table?TableDataName=$this->tableDataName&mode=none&q=" . $data['query'];
		return $destination;
	}

	/** Retrieves the data from the Database and conforms it to the overridden function's specifications. 
	 * @param array $request Any request parameters or data to specify the controller's operations. Typically a GET request.
	 */
	public function retrieveData(array $request = null): void
	{
		$model = $this->getModel();
		$modelData = $model->fetchModelData($request);
		$page = isset($request["page"]) ? $request["page"] : 1;
		$rows = isset($request["rows"]) ? $request["rows"] : 25;
		$sort = isset($request["sort"]) ? $request["sort"] : null;

		if (isset($modelData["ResultSet"])) {
			// Obtain the session table data:
			$tableSettings = isset($modelData[m\SESS_TABLES][$this->tableDataName]) ? $modelData[m\SESS_TABLES][$this->tableDataName] : null;

			$fb = new pb\FormBuilder("", null, null, "post", "Table?TableDataName=$this->tableDataName&mode=none");
			$searchBar = $fb->createInput('Search ', 'search', pb\InputTypes::search, ['value' => isset($request['q']) ? $request['q'] : null]);
			$fb->addFieldSet($searchBar, null);
			$this->setPreparedData('Search', $fb->buildContainer(null, null));
			$fb->__destruct();

			$tableSettings['TableDataName'] = $this->tableDataName;
			if (isset($request['q'])) {
				if (!empty($request['q'])) {
					$tableSettings['Query'] = $request['q'];
				}
			}

			$tb = new pb\TableBuilder("Results", null, $modelData["Name"], $rows, $page, $modelData["RecordCount"], $sort, $tableSettings);//$destination, $destVars, null, $groupColumn, $this->tableDataName);
			$tb->arrayToTable($modelData["ResultSet"]);
			$this->setPreparedData("TableContent", $tb->buildContainer());
			$tb->__destruct();
		}
		$this->getModel()->__destruct();
	}

	/**
	 * This function validates a search query made in the table
	 */
	protected function validateDataParameters(array $data, string $mode = 'default'): array | string | null
	{
		$validated = null;

		$validated['query'] = $this->validatePostInput($data['search']);

		return $validated;
	}
}
