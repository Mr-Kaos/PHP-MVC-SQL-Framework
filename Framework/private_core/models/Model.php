<?php

namespace Application\Model;

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
/** Specifies the retrieval of data from the database. */
const MODE_SELECT = 'select';

const SQL_PRINT_PREFIX = '/(?:\[Microsoft]\[ODBC Driver (\d+) for SQL Server]\[SQL Server])/';

/**
 * The base model class.
 * @author Kristian Oqueli Ambrose
 * 
 * Contains basic information necessary to define an object's data model.
 * 
 * @property string $dbSchema The database schema this model is sending/retrieving data from.
 * @property string $dbObject The database object (table, view) the model is sending/retrieving data from.
 * @property resource $conn An SQL Server connection resource.
 */
abstract class Model
{
	/** The database schema this model is sending/retrieving data from. */
	private string $dbSchema;
	/** The database object (table, view) the model is sending/retrieving data from. */
	private ?string $dbObject;
	/** An SQL Server connection resource. */
	private $conn;
	private $pageMode;

	/**
	 * Constructs the model with the base attributes.
	 * Creates an SQL connection resource, sets the database object to retrieve data from and sets the table identifiers for use in
	 * {@see \Application\Model\Model_Table}.
	 * 
	 * @param string $schema The name of the database schema of the database object the model will send/retrieve data from.
	 * @param ?string $objectName The name of the database object (typically a table or view) that the model will send/receive data from.
	 * @param string $mode The page mode.
	 * @param string $tableID Optional. If set, sets a table Identifier for use to create a table using Table.php. Used in {@see \Application\Model\Model_Table}.
	 */
	public function __construct(string $schema, ?string $objectName, string $mode, string $tableID = null)
	{
		$this->dbSchema = $schema;
		$this->dbObject = $objectName;
		$this->pageMode = $mode;
		$this->conn = $this->newConnection();
		$this->setTableIdentifiers($tableID, $schema, $objectName);
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
	 * Creates a session variable that defines the table to be displayed in Table.php.
	 * @param string $identifier The Session key that is used to identify the table.
	 * @param string $schema The database schema for the required table.
	 * @param string $object The database object name (i.e. table/view name)
	 * @param string $actionDestination The URL in which the action directs to. If null, will default to the current page. (optional)
	 * @param string $actionVariables The name of a column in the requested database table/view to use as an action for the row if clicked. (Optional)
	 * @param string $groupRowsBy If multiple rows need to be grouped together with another row (e.g. several rows share the same primary key), the column name that will group rows should be mentioned here.
	 */
	protected function setTableIdentifiers(?string $identifier, ?string $schema, ?string $object, string $actionDestination = null, array $actionVariables = null, array $hideRows = null, array $groupRowsBy = null): void
	{
		if (!is_null($identifier)) {
			$_SESSION[SESS_TABLES][$identifier] = array(
				"schema" => $schema,
				"object" => $object
			);
			if (!is_null($actionDestination)) {
				$this->setTableIdentifier($identifier, "destination", $actionDestination);
				$this->setTableIdentifier($identifier, "destinationVars", $actionVariables);
			}
			if (!is_null($groupRowsBy)) {
				$this->setTableIdentifier($identifier, "hideRows", $hideRows);
				$this->setTableIdentifier($identifier, "groupRowsBy", $groupRowsBy);
			}
		}
	}

	/**
	 * Modifies the specified session variable with the new data.
	 * If the existing data already contains the new data, it is replaced. Else, it is appended to the existing array.
	 * @param string $identifier The {@see SESS_TABLES} key that contains the data to modify.
	 * @param string $key The array key within the identifier's array to save the data to. If the key already exists, the data is replaced.
	 * @param mixed $data The data to append or replace in the specified array. Must be an associative array.
	 */
	protected function setTableIdentifier(string $identifier, string $key, mixed $data): void
	{
		$_SESSION[SESS_TABLES][$identifier][$key] = $data;
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
	 * - {@see \Application\Model\MODE_CREATE} Create:
	 * 	If creating a new table, all data related to an  except an Name should be given. Executes usp4000.
	 * - {@see \Application\Model\MODE_INSERT} Insert:
	 * If inserting data to a table, all data to be inserted along with the identifier for the table should be passed.
	 * - {@see \Application\Model\MODE_UPDATE}:
	 *	If updating an existing address record, the record's ID should be given along with data for each column.
	 * - {@see \Application\Model\MODE_DROP} Delete:
	 * 	If deleting an existing record, the record's ID should be given.
	 * Each of these methods should call their own stored procedure to 
	 * @return array|null A response message from the stored procedure if an error occurred. returns null if no error occurred.
	 */
	public abstract function sendModelData(array $data, string $submitMode = null): array | null;

	/**
	 * Performs a SELECT query in vw0001_TableFieldsExtra to retrieve the given database object's structure.
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
			FROM vw0001_TableFieldsExtra
			WHERE $keyClause OBJECT_SCHEMA = '$schema' AND OBJECT_NAME = '$object' $hiddenFieldsQry
			ORDER BY ORDINAL_POSITION ASC";
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
	 * @return array An array of the resultset retrieved
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
		if ($result) {
			$resultSet = $this->resultSetToArray($result);

			// If the resultset contains exactly one record, and it is not an array, set the resultset to the value.
			if (count($resultSet) == 1 && !is_array($resultSet[array_keys($resultSet)[0]])) {
				$resultSet = $resultSet[array_keys($resultSet)[0]];
			}
		} else {
			$_SESSION["MSG_ERROR"] = $this->displayErrors($qry)['FAILURE'];
		}

		return $resultSet;
	}

	/** 
	 * Gets the count oof the specified database object.
	 * @param string $schema The database schema of the object to query.
	 * @param string $objectName The name of the object to be queried.
	 * @param array $includedColumns An array of column names that will be included in the returned resultset. If null, all columns will be included. If there are exactly two columns specified, the result will be returned as an associative array, with the first column being the key.
	 * @param string $whereClause a WHERE clause to filter results with.
	 * @return int The number of rows of the specified columns in the database object
	 */
	protected function queryDatabaseObjectCount(string $schema = null, string $objectName = null, array $includedColumns = null, string $whereClause = ""): int
	{
		$count = 0;
		$qry = "SELECT COUNT(";
		if (is_null($schema) || is_null($objectName)) {
			$schema = &$this->dbSchema;
			$objectName = &$this->dbObject;
		}
		if (!is_null($includedColumns)) {
			foreach ($includedColumns as &$column) {
				$qry .= "[$column], ";
			}
		} else {
			$qry .= "* ";
		}
		$qry = $this->trimTrailingComma($qry);
		$qry .= ") FROM [$schema].[$objectName] $whereClause";
		$result = sqlsrv_query($this->getDBConnection(), $qry);
		if ($result) {
			$count = $this->resultSetToArray($result)[0];
		} else {
			$this->displayErrors($qry);
		}

		return $count;
	}

	/**
	 * Executes a stored procedure and returns its resultset (if one is generated) as an array.
	 * @param string $schema The schema name of th stored procedure.
	 * @param string $procedureName The name of the stored procedure to execute.
	 * @param array $params An associative array where each key is the name of a parameter in the specified stored procedure and the value is the
	 * value associated to that parameter.
	 * @param array $twoColsToOne If a resultset contains two or more columns, two columns can be specified and saved into a key-pair value.
	 * This parameter array must contain two keys:
	 * - {@see \Application\Model\TWO_COL_TO_ONE_PRIMARY}: The name of the column to be the array key
	 * - {@see \Application\Model\TWO_COL_TO_ONE_SECONDARY}: the name of the column to be the value
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

			if ($result) {
				$resultSet = $this->resultSetToArray($result);
			} else {
				$resultSet = $this->displayErrors($qry, $params);
			}
		}
		return $resultSet;
	}

	/**
	 * Converts a resultset object into an array.
	 * If the resultset contains one column, it returns the array as an associative array, rather than an array of arrays.
	 * @param mixed $resultSet The Resultset retrieved from an sqlsrv_query() call.
	 * @param bool $forceSubArray {@todo needs documentation} 
	 * @param array $twoColumnsToOne If a resultset contains two or more columns, two columns can be specified and saved into a key-pair value.
	 * This parameter array must contain two keys:
	 * - {@see \Application\Model\TWO_COL_TO_ONE_PRIMARY}: The name of the column to be the array key
	 * - {@see \Application\Model\TWO_COL_TO_ONE_SECONDARY}: the name of the column to be the value
	 * @return array The resultset's data in the form of an array.
	 */
	private function resultSetToArray(mixed $resultSet, bool $forceSubArray = false, array $twoColumnsToOne = null): array
	{
		$array = array();
		if ($resultSet) {
			$keys = null;
			$i = 0;
			while ($row = sqlsrv_fetch_array($resultSet, SQLSRV_FETCH_ASSOC)) {
				// If there is just on column (count === 1), make the resultset an associative array. else, return resultset as an array of arrays.
				if (!is_null($twoColumnsToOne)) {
					// Make sure the fields requested to be placed in an associative array exist.
					if (isset($row[$twoColumnsToOne[TWO_COL_TO_ONE_PRIMARY]]) && isset($row[$twoColumnsToOne[TWO_COL_TO_ONE_SECONDARY]])) {
						$array[$row[$twoColumnsToOne[TWO_COL_TO_ONE_PRIMARY]]] = $row[$twoColumnsToOne[TWO_COL_TO_ONE_SECONDARY]];
					}
				} elseif (count($row) === 1 && !$forceSubArray) {
					if (is_null($keys)) {
						$keys = array_keys($row);
					}
					$array[$i] = $row[$keys[0]];
					$i++;
				} else {
					array_push($array, $row);
				}
			}
		} else {
			array_push($array, "No ResultSet Acquired.");
		}

		// if the resultset's count is 1, and its first element is an array, set that array to be the main array.
		if (count($array) == 1 && isset($array[0])) {
			if (is_array($array[0])) {
				$array = $array[0];
			}
		}
		return $array;
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
	 * Retrieves the number of records that exist in the model's object.
	 * @param string $filters A WHERE Clause to filter the resultset by.
	 * The filter is passed off into {@see Model::getTableFilters()}.
	 * @return int The number of records in the model's database table/view.  
	 */
	// protected function getDBRecordCount(array $filters = null): int
	protected function getDBRecordCount(string $whereClause = null): int
	{
		$count = 0;

		// $filter = $this->getTableFilters($filters);
		$resultSet = sqlsrv_query($this->conn, "SELECT COUNT(*) FROM " . $this->getDBIdentifier() . " $whereClause");
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

	protected function getPage(): string
	{
		return $this->pageMode;
	}

	/**
	 * Retrieves data for any modals that need to be added to the page.
	 * Executes usp0021 to find all foreign keys for the model's database object.
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
		$modalReferences = $this->executeStoredProcedure('dbo', 'usp0021_sel_ForeignKeysForTableX', ["TableSchema" => $Schema, "TableName" => $Table]);
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
		$foreignKeyResultSet = array();
		foreach ($resultSet as $row) {
			//  Regular tables with explicit foreign keys will go through here
			if ($row["KEY_TYPE"] == "FOREIGN KEY") {
				$foreignKeyResultSet = empty($foreignKeyResultSet) ? array() : $foreignKeyResultSet;;
				$foreignKeyResultSet[$row["FIELD_NAME"]] = $this->fetchForeignKeyData($row["OBJECT_SCHEMA"], $row["OBJECT_NAME"], $row["FIELD_NAME"]);
				// Stored Procedure params go here
			} else if ($row["KEY_TYPE"] === "uspParam" && !is_null($row["PROPERTY_VALUES"])) {
				$qry = "SELECT TOP 1 * FROM dbo.ExtraProcedureParametersInfo WHERE PARAMETER_NAME = '" . $row["FIELD_NAME"] . "' AND SPECIFIC_NAME = '" . $row["OBJECT_NAME"] . "'";
				$refSchema = null;
				$refTable = null;
				$dataResultSet = sqlsrv_query($this->conn, $qry);
				if ($dataResultSet) {
					$uspData = sqlsrv_fetch_array($dataResultSet, SQLSRV_FETCH_ASSOC);
					$refSchema = $uspData["ReferencesTableSchema"];
					$refTable = $uspData["ReferencesTable"];
				}
				$foreignKeyResultSet[$row["FIELD_NAME"]] = $this->fetchForeignKeyData($refSchema, $refTable);
				// Extended properties 
			} else if (!is_null($row["PROPERTY_NAMES"])) {
				if (str_contains($row["PROPERTY_NAMES"], \Application\PageBuilder\PROPERTY_MULTI_FK_SCHEMA)) {
					$splitKeys = explode('`', $row["PROPERTY_NAMES"]);
					$splitValues = explode('`', $row["PROPERTY_VALUES"]);
					$valIndex = -1;

					$schema = null;
					$table = null;
					foreach ($splitKeys as $key) {
						$valIndex++;
						if ($key === \Application\PageBuilder\PROPERTY_MULTI_FK_SCHEMA) {
							$schema = $splitValues[$valIndex];
						} else if ($key === \Application\PageBuilder\PROPERTY_MULTI_FK_TABLE) {
							$table = $splitValues[$valIndex];
						}
					}

					$foreignKeyResultSet[$row["FIELD_NAME"]] = $this->fetchForeignKeyData($schema, $table);
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
			if (!is_null($column)) {
				$qry = "SELECT TOP 1 * FROM vw0005_ForeignKeyColumns WHERE TABLE_NAME = '$object' AND TABLE_SCHEMA = '$schema' AND COLUMN_NAME = '$column';";
				$fkResults = sqlsrv_query($this->conn, $qry);
				if ($fkResults) {
					$row = sqlsrv_fetch_array($fkResults, SQLSRV_FETCH_ASSOC);
					$object = $row["REFERENCED_TABLE_NAME"];
					$schema = $row["REFERENCED_TABLE_SCHEMA"];
				}
			}

			if (!is_null($schema)) {
				$qry = "EXEC usp0022_sel_TableRecordWithName @TableName = '$object', @SchemaName = '$schema'";
				$dataResultSet = sqlsrv_query($this->conn, $qry);
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
				if ($row["KEY_TYPE"] == "FOREIGN KEY") {
					$ok = true;
				} else if (!is_null($row['PROPERTY_NAMES'])) {
					if (str_contains($row['PROPERTY_NAMES'], 'MultiForeignKey')) {
						$ok = true;
					}
				}

				if ($ok) {
					$qry = "SELECT TOP 1 * FROM vw0010_AllForeignKeyReferences WHERE TABLE_NAME = '" . $row["OBJECT_NAME"] . "' AND TABLE_SCHEMA = '" . $row["OBJECT_SCHEMA"] . "' AND COLUMN_NAME = '" . $row["FIELD_NAME"] . "';";
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
				$msg .= "<hr>SQL Error ID: " . $error['code'] . '. Message: ' . $error['message'];
				switch ($error["code"]) {
						// Print statement
					case 0:
						$response["FAILURE"] .= preg_replace(SQL_PRINT_PREFIX, '', $error["message"]);
						break;
						// dropping non-existent table
					case 3701:
						$response["FAILURE"] .= 'The specified table could not be deleted as it does not exist.';
						break;
						// Dropping in-use column
					case 4922:
						$msg = preg_replace([SQL_PRINT_PREFIX, "ALTER TABLE DROP COLUMN "], '', $error["message"]);
						$cutoffPosition = strpos($msg, ' failed because');
						$fieldName = substr($msg, 0, $cutoffPosition);
						$msg = 'Cannot remove field "' . $fieldName . '" as it is referenced by other tables.';
						$response["FAILURE"] .= $msg;
						break;
						// Dropping non-existent column
					case 4924:
						$msg = preg_replace([SQL_PRINT_PREFIX, 'ALTER TABLE DROP COLUMN failed because column'], '', $error["message"]);
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
					default:
						if (!is_null($data)) {
							$msg .= '<br>Sent data: <pre>' . print_r($data, true) . '</pre>';
						}
						$msg .= '<hr>This is a temporary error message. Will be removed in production.';
						$response['FAILURE'] = 'An internal server error ocurred.';
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
		$serverName = "localhost";
		//$connectionInfo = array("Database" => "DB_NAME", "UID" => 'sa', "PWD" => 'password');
		$connectionInfo = array("Database" => "DB_NAME");
		$conn = sqlsrv_connect($serverName, $connectionInfo);
		if (!$conn) {
			$_SESSION["MSG_ERROR"] = 'Unable to connect to the database.';
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
