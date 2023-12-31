<?php
namespace EasyMVC\PageBuilder;

/** SQLToWebPage
 * 	This script defines an interface used to allow SQL result sets to build components of a webpage.
 */

require_once("PageBuilder.php");
const OPTION_EXCLUDED_FIELDS = 1;
const OPTION_USE_OBJECT_NAME_ID = 2;
const OPTION_FIELDNAME_PREFIX = 4;
const OPTION_PROCEDURE_PARAMS = 5;
const OPTION_APPEND_COLUMNS = 6;
const OPTION_INTERACTIVE_CELLS = 7;
const OPTION_ALLOW_WILDCARD = 8;
const OPTION_INCLUDE_PRIMARYKEY = 9;
const OPTION_ORDER_BY = 10;
const OPTION_SHOW_DISALLOWED_FIELDS = 11;
const OPTION_PREFILL_DATA = 12;

/**
 * Interface to create page elements from a database object.
 */
interface SQLToWebPage {

	/**
	 * @ignore - Needs to be adapted to use a non pre-built T-SQL stored procedure..
	 * Takes a table or view name from the connected database and executes a query to retrieve it's structure.
	 * Creates HTML form elements based on the query's results.
	 * @param array $resultSet An SQL Resultset of the queried data to be transformed into a form.
	 * @param array $options Additional params for extra options to filter out the resultset used to create page elements. The following options are accepted:
	 * 				- OPTION_EXCLUDED_FIELDS {array} - A list of fields (as an array) to be ignored from the resultset
	 * 				- OPTION_USE_OBJECT_NAME_ID {bool} - If true, will append the database object name to the id to reduce redundant IDs.
	 * @return string The constructed page elements. Returns Null if the $resultSet parameter is invalid.
	 */
	public function databaseObjectToPageElements(array $resultSet, array $options = null): string;
} 