<?php

namespace Application\Model;

require_once("Model.php");

/**
 * Used as a GET parameter in a URL to specify the table data key to retrieve from the session array $_SESSION[{@see SESS_TABLES}].
 * See {@see Model_Table::__construct} for more details.
 */
const TABLE_DATA_KEY = "TableDataName";

/**
 * The Model object for the Table page.
 * This class handles the retrieval and submission of data relating to Tables in the database.
 * Its other MVC components are:
 * - View: Table.php
 * - Controller: {@see \Application\Controller\Controller_Table}
 *
 * When {@see Model_Table::fetchModelData()} is called (page load), it retrieves the table structure of the address table to allow the controller
 * to build a form for it.
 * When {@see Model_Table::sendModelData()} is called, it either creates a new address record or updates an existing one. See {@see Model_Table::sendModelData()} for specifics.
 */
class Model_Table extends Model
{
	/** Constructor for Table model.
	 * Has a single parameter that is the key of the required table stored in the session's {@see SESS_TABLES} array.
	 * If the specified key exists in the session array, the table's name and schema are retrieved and used in the base model's constructor.
	 * If it is not found, the script terminates and no data is retrieved, resulting in the Table.php view displaying nothing.
	 * 
	 * @param string $tableDataName The name of the key in $_SESSION[{@see SESS_TABLES}].
	 * @return void
	 */
	public function __construct(string $tableDataName)
	{
		$tableData = &$_SESSION[SESS_TABLES][$tableDataName];
		if (!is_null($tableData)) {
			parent::__construct($tableData["schema"], $tableData["object"], 'default');
		} else {
			echo "Error - no tableData is set. Terminating.";
			die();
		}
	}

	/**
	 * Destroys this instance of the Model_Table object.
	 * @return void
	 */
	public function __destruct()
	{
		// unset($_SESSION[SESS_TABLES][$this->tableDataName]);
	}

	/**
	 * Fetches the specified number of records for the model's specified table/view.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 * 
	 * In the case for the Table MVC components, the following are the request values sent through the view and received here:
	 * - {@see TABLE_DATA_KEY}
	 * 	This constant should be used as a key in the request array. Its value is they key in $_SESSION[{@see SESS_TABLES}] that contains the specified table.
	 *  This array value is set in the respective table's model.
	 * - page
	 * 	Specifies the page number of the table to display.
	 * - row
	 * 	Specifies the number of rows to retrieve and display in the table.
	 */
	public function fetchModelData(array $request = null): array
	{
		$modelData = array();
		$modelData["Name"] = $this->getDBObjectName();
		$modelData[SESS_TABLES] = &$_SESSION[SESS_TABLES];

		if (is_null($page = isset($request["page"]) ? $request["page"] : null)) {
			$page = 0;
		} else {
			$page--;
		}

		if ($this->getDBConnection()) {
			$rows = isset($request["rows"]) ? $request["rows"] : 25;
			$filter = $this->getTableFilters(isset($request["TableFilter"]) ? $request["TableFilter"] : null);
			$orderBy = isset($request["sort"]) ? $request["sort"] : null;
			// determine if the ordering is to be ascending or descending
			$order = null;
			if (!is_null($orderBy)) {
				$orderVars = explode(',', $orderBy);
				if (isset($orderVars[1])) {
					if ($orderVars[1] == 1) {
						$order = ' DESC';
					} else {
						$order = ' ASC';
					}
				} else {
					$order = ' DESC';
				}
				$order = '[' . $orderVars[0] . ']' . $order;
			}

			if (is_null($order)) {
				$column = $this->queryDatabaseObject('INFORMATION_SCHEMA', 'COLUMNS', ['COLUMN_NAME'], "WHERE ORDINAL_POSITION = 1 AND TABLE_SCHEMA = '" . $this->getDBSchema() . "' AND TABLE_NAME = '" . $this->getDBObjectName() . "'");
				if (count($column) > 0) {
					$order = '[' . $column[array_keys($column)[0]] . ']';
				} else {
					echo "DEBUG: No records returned. " . $this->getDBIdentifier();
				}
			}
			$modelData["RecordCount"] = $this->getDBRecordCount(isset($request["TableFilter"]) ? $request["TableFilter"] : null);
			$modelData["ResultSet"] = $this->queryDatabaseObject($this->getDBSchema(), $this->getDBObjectName(), null, " $filter ORDER BY $order OFFSET " . $page * $rows . " ROWS FETCH NEXT $rows ROWS ONLY");
			
			// get the count of the first element in the resultset. If its count is greater than 1, the resultset contains more than one row. Else, only one row is returned, so it needs to be put back into array key 0.
			$firstKey = array_keys($modelData["ResultSet"])[0];

			if (!is_array(($modelData["ResultSet"][$firstKey]))) {
				$row = $modelData["ResultSet"];
				$modelData["ResultSet"] = null;
				$modelData["ResultSet"][0] = $row;
			}
		}

		return $modelData;
	}

	/** Does nothing
	 */
	public function sendModelData(array $data, string $submitMode = null): array | null
	{
		return null;
	}
}
