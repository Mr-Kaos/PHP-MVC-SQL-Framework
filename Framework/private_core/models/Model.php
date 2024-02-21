<?php

namespace EasyMVC\Model;

/** Array key used to specify a value to be used as an array key in Model::resultSetToArray() */
const TWO_COL_TO_ONE_PRIMARY = "PRIMARY_COL";
/** Array key used to specify a value to be used as an array value in Model::resultSetToArray() */
const TWO_COL_TO_ONE_SECONDARY = "SECONDARY_COL";
/** Array key used to store dependency data. Used in the fetchModelData function. */
const DATA_DEPENDENCIES = "Dependencies";
/** Array key used to store modal data references. Used in the fetchModelData function. */
const DATA_MODAL_REFS = "DATA_MODAL_REFS";
/** Array key used to store dependency data for dropdowns. Used in the fetchModelData function. */
const DATA_DROPDOWNS = "Dropdowns";
/** Array key used to store data for the main form in the view. Used in the fetchModelData function. */
const DATA_FORM_MAIN = "MainForm";
/** Array key used to store the values for the form when a record is being edited. */
const DATA_FORM_EDIT = "FormEditData";
/** Array key used to store table identifiers in the $_SESSION array. */
const SESS_TABLES = "TABLES";
/** Array key used to store data for a RESTful GET request */
const DATA_REST_GET = "RestfulGET";


/** Specifies the insertion of a new record */
const MODE_INSERT = 'add';
/** Specified the updating of a record */
const MODE_UPDATE = 'update';
/** Specifies the deletion of a record */
const MODE_DELETE = 'delete';
/** Specifies the creation of a new table */
const MODE_CREATE = 'new';
/** Specifies the dropping of a user-generated table */
const MODE_DROP = 'drop';
/** Specifies the modification of a user-generated table*/
const MODE_EDIT = 'edit';
/** */
const MODE_SELECT = 'select';

const SQL_PRINT_PREFIX = '[Microsoft][ODBC Driver 17 for SQL Server][SQL Server]';

/**
 * The base model class.
 * @author Kristian Oqueli Ambrose
 * 
 * Contains basic information necessary to define an object's data model.
 * All data models are based off a table/view in the AbleFormes database.
 * 
 * @property string $dbSchema The database schema this model is sending/retrieving data from.
 * @property string $dbObject The database object (table, view) the model is sending/retrieving data from.
 * @property resource $conn An SQL Server connection resource.
 */
abstract class Model
{
	/** The database schema this model is sending/retrieving data from. */
	private ?string $dbSchema;
	/** The database object (table, view) the model is sending/retrieving data from. */
	private ?string $dbObject;
	/** An SQL Server connection resource. */
	private $conn;
	private $mode;

	/**
	 * Constructs the model with the base attributes.
	 * Creates an SQL connection resource, sets the database object to retrieve data from and sets the table identifiers for use in
	 * {@see \EasyMVC\Model\Model_Table}.
	 * 
	 * @param string $schema The name of the database schema of the database object the model will send/retrieve data from.
	 * @param ?string $objectName The name of the database object (typically a table or view) that the model will send/receive data from.
	 * @param string $mode The page mode.
	 * @param string $tableID Optional. If set, sets a table Identifier for use to create a table using Table.php. Used in {@see \EasyMVC\Model\Model_Table}.
	 */
	public function __construct(?string $schema, ?string $objectName, string $mode)
	{
		$this->dbSchema = $schema;
		$this->dbObject = $objectName;
		$this->mode = $mode;
		$this->conn = (constant('REQUIRE_DB')) ? $this->newConnection() : null;
	}

	/**
	 * Destroys the instance of a model.
	 * Closes any open SQL Server connections.
	 */
	public function __destruct()
	{
		$this->closeConnection($this->conn);
	}

	/**
	 * Fetches data from the database to update the model.
	 * Takes a request received from the view through to the controller to specify what data is required.
	 * @param array $request An associative array containing any requests to specify what data is required from the implemented model.
	 * @return array The data retrieved from the database.
	 */
	public abstract function fetchModelData(array $request = null): array | null;

	/**
	 * Sends data to the database via a stored procedure.
	 * Returns an address (URI) in which the page should redirect to after the data has been submitted.
	 * 
	 * @param array $data An associative array of the data to be submitted to the database.
	 * @param string $submitMode A constant specifying the method being used to submit data. The allowed modes are:
	 * - {@see \EasyMVC\Model\MODE_CREATE} Create:
	 * 	If creating a new table, all data related to an  except an Name should be given. Executes usp_ins_NewEnum.
	 * - {@see \EasyMVC\Model\MODE_INSERT} Insert:
	 * If inserting data to a table, all data to be inserted along with the identifier for the table should be passed.
	 * - {@see \EasyMVC\Model\MODE_UPDATE}:
	 *	If updating an existing address record, the record's ID should be given along with data for each column.
	 * - {@see \EasyMVC\Model\MODE_DROP} Delete:
	 * 	If deleting an existing record, the record's ID should be given.
	 * Each of these methods should call their own stored procedure to 
	 * @return array|null A response message from the stored procedure if an error occurred. returns null if no error occurred.
	 */
	public abstract function sendModelData(array $data, string $submitMode = null): array | null;

	/**
	 * Performs a SELECT query in vw_TableFields to retrieve the given database object's structure.
	 * The structure includes all columns for tables and views and their various properties.
	 * This function should not be used to retrieve the structure of stored procedures.
	 * If no schema or object is passed into the function, the ones used in the model's initialisation are used instead.
	 * 
	 * @param string $schema The database schema for the required table.
	 * @param string $object The database object name (i.e. table/view name)
	 * @param array $excludedFields An array of table column names that should not be returned.
	 * @return ?array Returns the resultset of the database object's structure. If none was retrieved (i.e. the specified object
	 * does not exist), null is returned.
	 */
	protected function getDBObjectStructure(string $schema = null, string $object = null, array $excludedFields = null): ?array
	{
		$hiddenFieldsQry = "";
		$resultSet = null;

		if ($this->conn) {
			$schema = empty($schema) ? $this->dbSchema : $schema;
			$object = empty($object) ? $this->dbObject : $object;

			// if ($includePk) {
			$keyClause = "";
			// }
			if ($excludedFields) {
				foreach ($excludedFields as &$field) {
					$hiddenFieldsQry .= " AND FIELD_NAME <> '$field'";
				}
			}
			$qry = "SELECT *
			FROM vw_DatabaseObjectFields
			WHERE $keyClause [OBJECT_SCHEMA_NAME] = '$schema' AND [OBJECT_NAME] = '$object' $hiddenFieldsQry
			ORDER BY [ORDINAL_POSITION] ASC";
			$resultSet = $this->resultSetToArray(sqlsrv_query($this->conn, $qry));
		}

		return $resultSet;
	}

	/** 
	 * Queries the specified database object and returns the results.
	 * If the query returns a result set that contains multiple rows and columns, it will return each row as an array, where each row is accessed by a numeric key.
	 * If the query returns a result set that contains multiple rows but has one column, the column name will be the key of the array, with each row as a value within.
	 * If the query returns a result set that contains one row and one column, the value of that row will be returned as its respective PHP data type.
	 * 
	 * @param string $schema The database schema of the object to query.
	 * @param string $objectName The name of the object to be queried.
	 * @param array $includedColumns An array of column names that will be included in the returned resultset. If null, all columns will be included. If there are exactly two columns specified, the result will be returned as an associative array, with the first column being the key.
	 * @param string $whereClause a WHERE clause to filter results with.
	 * @return array|string|null An array of the resultset retrieved
	 */
	protected function queryDatabaseObject(string $schema = null, string $objectName = null, array $includedColumns = null, string $whereClause = ""): array | string | null
	{
		$resultSet = null;
		$qry = "SELECT ";
		if (is_null($schema) || is_null($objectName)) {
			$schema = &$this->dbSchema;
			$objectName = &$this->dbObject;
		}
		if (!is_null($includedColumns)) {
			foreach ($includedColumns as &$column) {
				str_replace(["'", "`"], '', $column);
				$qry .= "[$column], ";
			}
		} else {
			$qry .= "* ";
		}
		$qry = $this->trimTrailingComma($qry);
		$qry .= " FROM [$schema].[$objectName] $whereClause";

		$result = sqlsrv_query($this->getDBConnection(), $qry);
		if (constant('LOG_SQL')) {
			error_log(get_class($this));
			error_log($qry);
		}
		if ($result) {
			$resultSet = $this->resultSetToArray($result);

			// If the resultset contains exactly one record, and it is not an array, set the resultset to the value.
			if (count($resultSet) == 1 && !is_array($resultSet[array_keys($resultSet)[0]])) {
				$resultSet = $resultSet[array_keys($resultSet)[0]];
			}
		} else {
			new \EasyMVC\Components\Notification($this->displayErrors($qry)['FAILURE'], \EasyMVC\Components\NotificationType::Error);
		}

		return $resultSet;
	}

	/**
	 * Executes a stored procedure and returns its resultset (if one is generated) as an array.
	 * @param string $schema The schema name of th stored procedure.
	 * @param string $procedureName The name of the stored procedure to execute.
	 * @param array $params An associative array where each key is the name of a parameter in the specified stored procedure and the value is the
	 * value associated to that parameter.
	 */
	protected function executeStoredProcedure(string $schema, string $procedureName, array $params): ?array
	{
		$resultSet = null;
		if ($this->conn) {
			$qry = "EXEC [$schema].[$procedureName]";
			$parameters = array();

			foreach ($params as $param => &$value) {
				$qry .= " @$param = ?,";
				array_push($parameters, array(&$value, SQLSRV_PARAM_IN));
			}
			$qry = $this->trimTrailingComma($qry);

			$result = sqlsrv_query($this->conn, $qry, $parameters);
			if (constant('LOG_SQL')) {
				error_log(get_class($this));
				error_log($qry);
				error_log(print_r($params, true));
			}

			if ($result) {
				$resultSet = $this->resultSetToArray($result);
			} else {
				$resultSet = $this->displayErrors($qry, $params);
			}
		}
		return $resultSet;
	}

	/**
	 * Simplifies the given array to a more readable and cleaner format.
	 * @param array $resultSet The array of the resultset to be simplified
	 * @param bool $valueIsKey If the key of the array should also the the value, enable this option.
	 */
	protected function simplifyResultsetArray(array $resultSet, bool $valueIsKey = false): array
	{
		$array = [];
		foreach ($resultSet as $val) {
			$key = array_keys($val)[0];
			if (isset($array[$key])) {
				if ($valueIsKey) {
					$array[$val[$key]] = $val[$key];
				} else {
					array_push($array, $val[$key]);
				}
			} else {
				if ($valueIsKey) {
					$array[$val[$key]] = $val[$key]; // => $val[$key]];
				} else {
					array_push($array, $val[$key]);
				}
			}
		}
		return $array;
	}

	/**
	 * Converts a resultset object into an array.
	 * If the resultset contains one column, it returns the array as an associative array, rather than an array of arrays.
	 * @param mixed $resultSet The Resultset retrieved from an sqlsrv_query() call.
	 * @param bool $forceSubArray {@todo needs documentation} 
	 * @param array $twoColumnsToOne If a resultset contains two or more columns, two columns can be specified and saved into a key-pair value.
	 * This parameter array must contain two keys:
	 * - {@see \EasyMVC\Model\TWO_COL_TO_ONE_PRIMARY}: The name of the column to be the array key
	 * - {@see \EasyMVC\Model\TWO_COL_TO_ONE_SECONDARY}: the name of the column to be the value
	 * @return array The resultset's data in the form of an array.
	 */
	private function resultSetToArray(mixed $stmt): array
	{
		$results = array();
		$resultSet = $stmt;

		while (!is_null($resultSet)) {
			$array = array();
			$singleColumn = false;
			if ($resultSet !== false) {
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					array_push($array, $row);
					if (count($row) == 1) {
						$singleColumn = true;
					}
				}
			} else {
				array_push($array, "No ResultSet Acquired.");
				$resultSet = null;
			}

			if ($singleColumn) {
				$array = $this->simplifyResultsetArray($array);
			}

			// if the resultset's count is 1, and its first element is an array, set that array to be the main array.
			if (count($array) == 1 && isset($array[0])) {
				if (is_array($array[0])) {
					$array = $array[0];
				}
			}
			array_push($results, $array);

			if ($stmt !== false) {
				$resultSet = sqlsrv_next_result($stmt);
			}
		}

		if (count($results) == 1) {
			$results = $results[0];
		}

		return $results;
	}

	/**
	 * Retrieves the number of records that exist in the model's object.
	 * @param string $whereClause A WHERE clause to filter the resultset by.
	 * The filter is passed off into {@see Model::getTableFilters()}.
	 * @param string $schema The schema of a table or view to select the count for. If omitted, uses the model's set database identifiers. If given, the $object parameter must also be given.
	 * @param string $object The name of the table or view to select the count for. If omitted, uses the model's set database identifiers.
	 * @return int The number of records in the model's database table/view.  
	 */
	// protected function getDBRecordCount(array $filters = null): int
	protected function getDBRecordCount(string $whereClause = null, string $schema = null, string $object = null): int
	{
		$count = 0;
		$resultSet = false;

		if (!is_null($schema) && !is_null($object)) {
			$resultSet = sqlsrv_query($this->conn, "SELECT COUNT(*) FROM [$schema].[$object] $whereClause");
		} else {
			$resultSet = sqlsrv_query($this->conn, "SELECT COUNT(*) FROM " . $this->getDBIdentifier() . " $whereClause");
		}
		if ($resultSet) {
			$count = sqlsrv_fetch_array($resultSet, SQLSRV_FETCH_NUMERIC)[0];
		}
		return $count;
	}

	/**
	 * Checks if the session variable "TableFilter" is set and retrieves any filters from there and appends them to a WHERE clause string. 
	 * Optionally, an array with filter values can be given. If no array of filters values are given, the SESSION variable will be used instead.
	 * @param array $filters An associative array where the key is the column name and the value is a value to filter by (as used in a WHERE clause).
	 * @return string The a WHERE clause of an SQL query constructed from the $filter array.
	 */
	protected function getTableFilters(array $filters = null): string
	{
		$filter = "";
		$filterArray = null;
		if (!is_null($filters)) {
			$filterArray = &$filters;
			// } else if (isset($_SESSION["TableFilter"])) {
			// 	$filterArray = &$_SESSION["TableFilter"];
		}

		if (!is_null($filterArray)) {
			$filter = " WHERE";
			foreach ($filterArray as $column => $value) {
				$filter .= " $column = $value";
			}
		}

		return $filter;
	}

	/**
	 * Returns a connection resource to the database. Only usable in inherited models.
	 * @return resource a SQL Server connection resource.
	 */
	protected function getDBConnection()
	{
		return $this->conn;
	}

	/**
	 * Returns the database schema and object name as a concatenated string, separated by a period.
	 * @return string The database schema and object in standard T-SQL.
	 */
	protected function getDBIdentifier(): string
	{
		return '[' . $this->dbSchema . '].[' . $this->dbObject . ']';
	}

	/**
	 * Returns the name of the Database object name.
	 * @return string The model's {@see Model::$dbObject} property.
	 */
	protected function getDBObjectName(): ?string
	{
		return $this->dbObject;
	}

	/**
	 * Returns the schema name of the db object.
	 * @return string The model's {@see Model::$dbSchema} property.
	 */
	protected function getDBSchema(): ?string
	{
		return $this->dbSchema;
	}

	/**
	 * Returns the page the controller is being used in. The page is the filename from a View's directory, excluding the file extension.
	 * @return string The page name
	 */
	protected function getPage(): string
	{
		return $this->mode;
	}

	/**
	 * Retrieves data for any modals that need to be added to the page.
	 * Executes usp_sel_ForeignKeysForTableX to find all foreign keys for the model's database object.
	 * @param $SchemaOverride string If the modal is in a different schema than the model's schema, this parameter can be used to override the schema.
	 * @param $TableOverride string If the modal is in a different table than the model's table, this parameter can be used to override the table.
	 * @return array An associative array where each key is the name of a column that has a modal and its value is the schema, table and column that it references.
	 */
	protected function getModalReferences($SchemaOverride = null, $TableOverride = null): array
	{
		$result = array();
		if ($SchemaOverride != null) {
			$Schema = $SchemaOverride;
		} else {
			$Schema = $this->getDBSchema();
		}
		if ($TableOverride != null) {
			$Table = $TableOverride;
		} else {
			$Table = $this->getDBObjectName();
		}
		$modalReferences = $this->executeStoredProcedure('dbo', 'usp_sel_ForeignKeysForTableX', ["TableSchema" => $Schema, "TableName" => $Table]);
		// Clean up ModalReferences array for use in controller:
		// This is done by replacing the numeric array key with the column name instead.
		$numericKeys = count($modalReferences);
		if ($numericKeys > 0) {
			foreach ($modalReferences as $data) {
				$result[$data["ColumnName"]] = $data;
			}

			for ($i = 0; $i < $numericKeys; $i++) {
				unset($result[$i]);
			}
		}
		return $result;
	}

	/**
	 * Checks a resultset obtained from {@see Model::getDBObjectStructure()} for any data dependencies that need to be included or used in the controller.
	 * This includes foreign key fields or other result sets that need to be included e.g. for dropdown lists).
	 * 
	 * @param array The associative array obtained from {@see Model::getDBObjectStructure()}.
	 * @return ?array An associative array containing all of the dependency data for the given resultset.
	 */
	protected function checkForForeignKeyDependencies(array &$resultSet): ?array
	{
		$foreignKeyResultSet = null;

		// If only one column is in the resultset, reformat the array as if it contained multiple
		if (!isset($resultSet[0])) {
			$resultSet = [$resultSet];
		}

		// ensure that each row is of type array.
		if (is_array($resultSet[0])) {
			$foreignKeyResultSet = array();
			foreach ($resultSet as $row) {
				//  Regular tables with explicit foreign keys will go through here
				if (isset($row['REFERENCED_SCHEMA_NAME'])) {
					$foreignKeyResultSet[$row["FIELD_NAME"]] = $this->fetchForeignKeyData($row["REFERENCED_SCHEMA_NAME"], $row["REFERENCED_TABLE_NAME"], $row["REFERENCED_COLUMN_NAME"]);
				} elseif (!is_null($row['PROPERTY_NAMES'])) {
					if (str_contains($row['PROPERTY_NAMES'], 'MultiForeignKeySchema')) {
						$properties = array_combine(explode("`", $row["PROPERTY_NAMES"]), explode("`", $row["PROPERTY_VALUES"]));
						$foreignKeyResultSet[$row["FIELD_NAME"]] = $this->fetchForeignKeyData($properties["MultiForeignKeySchema"], $properties["MultiForeignKeyTable"], null);
					}
				}
			}
		}

		return $foreignKeyResultSet;
	}

	/**
	 * Fetches the foreign key data dependencies associated to the model.
	 * @param string $schema The database schema the foreign key is referencing.
	 * @param string $object The database object the foreign key is referencing.
	 * @param string $column The column within the database object that the foreign key is referencing.
	 * @return array|null The resultset of the gathered data the foreign key points to.
	 */
	private function fetchForeignKeyData(string $schema, string $object, string $column = null): ?array
	{
		$fkData = null;

		if ($this->conn) {
			if (is_null($column)) {
				$column = $this->queryDatabaseObject('dbo', 'vw_TableFields', ['COLUMN_NAME'], "WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$object' AND PRIMARY_KEY = 1");
			}

			if (!is_null($schema)) {
				$qry = "EXEC usp_sel_TableRecordWithName @TableName = '$object', @SchemaName = '$schema', @PrimaryKey = '$column'";
				$dataResultSet = sqlsrv_query($this->conn, $qry);
				if (constant('LOG_SQL')) {
					error_log(get_class($this));
					error_log($qry);
				}
				if ($dataResultSet) {
					$count = 0;
					while ($row = sqlsrv_fetch_array($dataResultSet, SQLSRV_FETCH_ASSOC)) {
						$fkData = empty($fkData) ? array() : $fkData;
						$fkData[$row["NAME"]] = $row["VALUE"];
						$count++;
					}

					if ($count === 0) {
						$fkData = array();
					}
				} else {
					$this->displayErrors($qry);
				}
			}
		}

		return $fkData;
	}

	/**
	 * Finds and returns the table name, schema and column that the given data references.
	 * Data given should be retrieved from @see{/Model/getDBObjectStructure}.
	 * 
	 */
	protected function getForeignKeyReferences(array $data): array | null
	{
		$fkResult = null;
		if ($this->conn) {
			$fkResult = array();

			foreach ($data as $row) {
				$ok = false;
				if (!is_null($row["REFERENCED_SCHEMA_NAME"])) {
					$ok = true;
				} else if (!is_null($row['PROPERTY_NAMES'])) {
					if (str_contains($row['PROPERTY_NAMES'], 'MultiForeignKey')) {
						$ok = true;
					}
				}

				if ($ok) {
					$qry = "SELECT TOP 1 * FROM vw_AllForeignKeyReferences WHERE TABLE_NAME = '" . $row["OBJECT_NAME"] . "' AND TABLE_SCHEMA = '" . $row["OBJECT_SCHEMA_NAME"] . "' AND COLUMN_NAME = '" . $row["FIELD_NAME"] . "';";
					$fkResults = sqlsrv_query($this->conn, $qry);
					if ($fkResults) {
						$fkRow = sqlsrv_fetch_array($fkResults, SQLSRV_FETCH_ASSOC);
						if (!is_null($fkRow)) {
							$fkResult[$row["FIELD_NAME"]]['Table'] = $fkRow["REFERENCED_TABLE_NAME"];
							$fkResult[$row["FIELD_NAME"]]['Schema'] = $fkRow["REFERENCED_TABLE_SCHEMA"];
						}
					}
				}
			}
		}

		return $fkResult;
	}

	/**
	 * Catches and outputs user-friendly messages for any errors that occur. Primarily for use with SQL queries.
	 * If an uncaught error is received, it logs the error to the php log file.
	 * @param string $qry The query that was executed and resulted in an error.
	 * @param array $data The data that was passed into the query, if the query required data.
	 */
	private function displayErrors(string $qry, array $data = null): array | null
	{
		$response = [];

		if (sqlsrv_errors() !== null) {
			$response["FAILURE"] = '';
			$msg = "Failure executing SQL query.\nQuery: $qry\nSQL Server Messages:";

			foreach (sqlsrv_errors() as $error) {
				$msg .= "\nCode: " . $error['code'] . ' | message: ' . $error['message'];
				switch ($error["code"]) {
						// Print statement
					case 0:
						$response["FAILURE"] .= str_replace(SQL_PRINT_PREFIX, '', $error["message"]);
						break;
						// Deleting record with foreign key constraint
					case 547:
						$response["FAILURE"] .= 'The specified record cannot be deleted as it is referenced by a record in another table.';
						break;
						// dropping non-existent table
					case 3701:
						$response["FAILURE"] .= 'The specified table could not be deleted as it does not exist.';
						break;
						// Dropping in-use column
					case 4922:
						$msg = str_replace([SQL_PRINT_PREFIX, "ALTER TABLE DROP COLUMN "], '', $error["message"]);
						$cutoffPosition = strpos($msg, ' failed because');
						$fieldName = substr($msg, 0, $cutoffPosition);
						$msg = 'Cannot remove field "' . $fieldName . '" as it is referenced by other tables.';
						$response["FAILURE"] .= $msg;
						break;
						// Dropping non-existent column
					case 4924:
						$msg = str_replace([SQL_PRINT_PREFIX, 'ALTER TABLE DROP COLUMN failed because column'], '', $error["message"]);
						$cutoffPosition = strpos($msg, 'does not exist');
						$msg = 'Failed to remove field' . substr($msg, 0, $cutoffPosition) . ' as it does not exist.';
						$response["FAILURE"] .= $msg;
						break;
						// Foreign Key dependency
					case 5074:
						break;
						// sp_rename
					case 15477:
						$response = null;
						break;
						// Catch for "statement has been terminated" error
					case 3621:
						break;
					default:
						if (!is_null($data)) {
							$msg .= '<br>Sent data: <pre>' . print_r($data, true) . '</pre>';
						}
						$msg .= 'This is a temporary error message. To be removed in production.';
						$response["FAILURE"] = "An uncaught exception has occurred.\n";
				}
				error_log($msg, 0);
			}
		}
		return $response;
	}

	/**
	 * Creates a new Connection resource to the SQL Server.
	 * @return resource An SQL Server connection resource.
	 */
	private function newConnection(): mixed
	{
		$connectionInfo = array("Database" => constant('DB_NAME'), "UID" => constant('DB_USERNAME'), "PWD" => constant('DB_PASSWORD'), "CharacterSet" => "UTF-8");
		$conn = sqlsrv_connect(constant('DB_SERVER'), $connectionInfo);
		if (!$conn) {
			new \EasyMVC\Components\Notification('Unable to connect to the database.', \EasyMVC\Components\NotificationType::Error);
		}
		return $conn;
	}

	/**
	 * Closes the specified connection resource.
	 * @param resource The connection resource to close.
	 */
	private function closeConnection(mixed &$conn): void
	{
		if ($conn) {
			sqlsrv_close($conn);
		}
	}

	/** Removes any trailing delimiters if they are found.
	 * @param string $CSVString The string to remove any trailing commas from
	 * @param string $delim The delimiter to check for, should it exist. Defaults to a comma.
	 * @return string The CSV string without the trailing comma.
	 */
	private function trimTrailingComma(string $CSVString, string $delim = ','): string
	{
		$CSVString = trim($CSVString);
		if (substr($CSVString, -1) == $delim) {
			$CSVString = substr($CSVString, 0, -1);
		}
		return $CSVString;
	}
}
