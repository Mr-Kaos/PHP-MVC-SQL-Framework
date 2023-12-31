<?php

namespace EasyMVC\Controller;

/**
 * This trait class contains special data validation functions that are used in specific controllers.
 * This is typically used in controllers that need to validate data in the form of CSV or JSON strings.
 */
trait DataValidator
{
	/**
	 * Used primarily for the validation of creating new tables and columns in existing tables.
	 * Takes an associative array containing the data to be used in the creation of a new table/column(s) and parses
	 * through it, creating CSV strings and storing them into an array with their respective keys.
	 * 
	 * The parsed and validated data is sent into a model which executes a database stored procedure that utilises the CSV values and their order. 
	 * @param array $data The associative array containing the data to parse into CSV values.
	 * @param  bool $hasSelectableAttribute Set to tru if the table/column being created has the option to have the new columns selectable.
	 * @return array The validated data where each key's value is a CSV for use in the model's stored procedure.
	 */
	private function validateCSVParams(array $data, $keyPrefix = ""): array
	{
		$CSVData = array();
		$lastName = '';
		$attributeNames = "";
		$attributeFriendlyNames = "";
		$attributeTypes = "";
		$attributeValues = "";
		$AttributeRequired = "";
		$attributeOverrides = "";

		foreach ($data as $key => $val) {
			if (strpos($key, "attrName") === 0) {
				$lastName = preg_replace('/\bmulti-/i', '', $val);
				$lastName = $this->formatNameForDatabase($lastName);

				$attributeNames .= $lastName . "`";
				$attributeFriendlyNames .= $this->validateInput($val) . "`";
			} else if (strpos($key, "attrType") === 0) {
				$attributeTypes .= $this->dataTypeConversion($val) . '`';
			} else if (strpos($key, "attrVal") === 0) {
				$attributeValues .= "$val`";
			} else if (strpos($key, "attrNull") === 0) {
				$AttributeRequired .= "$val`";
			} else if (strpos($key, "attrSelectable") === 0) {
				$attributeOverrides .= $this->validatePostInput($val, false) . "`";
			} else if ($key === "identifier") {
				$CSVData["IsNameColumn"] = $lastName;
			}
		}

		$CSVData[$keyPrefix . "AttributeNames"] = $attributeNames;
		$CSVData[$keyPrefix . "AttributeFriendlyNames"] = $attributeFriendlyNames;
		$CSVData[$keyPrefix . "AttributeTypes"] = $attributeTypes;
		$CSVData[$keyPrefix . "AttributeValues"] = $attributeValues;
		$CSVData[$keyPrefix . "AttributeRequired"] = $AttributeRequired;
		if (strlen($attributeOverrides) > 0) {
			$CSVData[$keyPrefix . "AttributeOverrides"] = $attributeOverrides;
		}
		return $CSVData;
	}

	/**
	 * Used for the validation of editing user-generated tables.
	 * Takes an associative array containing the data to be used in the creation of a new table/column(s) and parses
	 * through it, creating CSV strings and storing them into an array with their respective keys.
	 * 
	 * The parsed and validated data is sent into a model which executes a database stored procedure that utilises the CSV values and their order. 
	 * @param array $data The associative array containing the data to parse into CSV values.
	 * @param  bool $hasSelectableAttribute Set to true if the table/column being created has the option to have the new columns selectable.
	 * @return array The validated data where each key's value is a CSV for use in the model's stored procedure.
	 */
	private function validateCSVParamsForEdit(array &$data): array
	{
		$CSVData = array();
		$originalNames = '';
		$columnRenames = '';
		$columnTypes = '';
		$columnValues = '';
		$columnNulls = '';
		$columnOverride = '';
		$columnsToRemove = '';
		$newFields = null;
		$spliceOffset = 0;
		$organised = array();
		$currentIndex = 0;

		// Loops through each piece of data posted, and assigns them to a string for use in the required stored procedure.
		// If the "NEW_FIELDS_START" key is found, all following values in the array should be new fields to be parsed using the standard validateCSVParams function.

		// Loops through each piece of data posted that needs to be parsed into CSV strings. This loop stores them in a temporary array which separates each value
		// into their respective row for easy manipulation.
		foreach ($data as $key => $val) {
			if ($key === 'NEW_FIELDS_START') {
				$newFields = $this->validateCSVParams(array_splice($data, $spliceOffset), 'New');
				break;
			} elseif (substr($key, strlen($key) - 1, strlen($key)) == $currentIndex) {
				$organised[$currentIndex][$key] = $val;
			} else if ($key === 'identifier') {
				$organised[$currentIndex][$key] = $val;
			} else {
				$currentIndex++;
				$organised[$currentIndex][$key] = $val;
			}
			$spliceOffset++;
		}

		for ($i = 0; $i < count($organised); $i++) {
			$separator = $i + 1 < count($organised) ? '`' : '';
			$originalNames .= isset($organised[$i]['attrOriginalName' . $i]) ? $this->formatNameForDatabase($organised[$i]['attrOriginalName' . $i]) . $separator : '`';
			$columnRenames .= isset($organised[$i]['attrName' . $i]) ? $this->validatePostInput($organised[$i]['attrName' . $i]) . $separator : '`';
			$columnTypes .= isset($organised[$i]['attrType' . $i]) ? $this->dataTypeConversion($organised[$i]['attrType' . $i]) . $separator : '`';
			$columnValues .= isset($organised[$i]['attrVal' . $i]) ? $this->validatePostInput($organised[$i]['attrVal' . $i]) . $separator : '`';
			$columnNulls .= isset($organised[$i]['attrNull' . $i]) ? $this->validatePostInput($organised[$i]['attrNull' . $i]) . $separator : '`';
			$columnOverride .= isset($organised[$i]['attrSelectable' . $i]) ? $this->validatePostInput($organised[$i]['attrSelectable' . $i]) . $separator : '`';
			if (isset($organised[$i]['identifier'])) {
				$CSVData["IsNameColumn"] = $organised[$i]['attrOriginalName' . $i];
			}

			if (isset($organised[$i]['attrSelectable' . $i])) {
				$columnOverride .= $this->validatePostInput($organised[$i]['attrSelectable' . $i]) . $separator;
			} else {
				$columnOverride .= '`';
			}
		}

		$CSVData["OriginalColumnNames"] = $originalNames;
		$CSVData["EditColumnNames"] = $columnRenames;
		$CSVData["EditColumnTypes"] = $columnTypes;
		$CSVData["EditColumnValues"] = $columnValues;
		$CSVData["EditColumnNulls"] = $columnNulls;
		if (strlen($columnOverride) > 0) {
			$CSVData["EditColumnOverrides"] = $columnOverride;
		}
		$CSVData["RemoveColumnNames"] = $columnsToRemove;
		if (!is_null($newFields)) {
			$CSVData = array_merge($CSVData, $newFields);
		}

		unset($CSVData['NewAttributeRequired']);
		return $CSVData;
	}

	/** Takes the non-SQL typename and convert it to the respective SQL typename */
	private function dataTypeConversion(string $val): ?string
	{
		$typeName = null;
		switch ($val) {
			case "text":
				$typeName .= "VARCHAR";
				break;
			case "textUTF":
				$typeName .= "NVARCHAR";
				break;
			case "number":
				$typeName .= "INT";
				break;
			case "decimal":
				$typeName .= "DECIMAL";
				break;
			case "boolean":
				$typeName .= "BIT";
				break;
			case "enum":
				$typeName .= "FK";
				break;
		}

		return $typeName;
	}

	/**
	 * This is used to format Product attributes into a JSON string.
	 * This is used when submitting a new order, a new routing task or a machine product configuration.
	 * @param array $data - An array containing the data to be parsed and validated for use in an MVC model.
	 * @param array | @deprecated $fieldsToIgnore  An array containing keys in the $data that should not be parsed and included in the output JSON. 
	 * @return string - the validated data as an array.
	 */
	private function componentAttributeToJSON(?array $data, array $fieldsToIgnore = array()): string
	{
		$validated = array();
		if (!is_null($data)) {
			$componentsArray = array();
			$subArray = array();
			$subArrayName = '';
			$multiPostKey = null;
			$tempMultiPostValues = array();
			$removeProductName = false;
			$cleansedName = '';
			$productsFound = false;

			foreach ($data as $var => $val) {
				if (!is_numeric(array_search($var, $fieldsToIgnore))) {
					if ($removeProductName && !empty($subArrayName)) {
						$cleansedName = str_replace($subArrayName . '_', '', $var);
					}
					// If the next component attribute is not from a pillbox input but the pillbox key is set, assign the previously obtained pillbox values
					if (!is_null($multiPostKey) && substr($var, 0, 6) !== 'multi-') {
						if (count($tempMultiPostValues) > 0) {
							$subArray[$multiPostKey] = $tempMultiPostValues;
						}
						$tempMultiPostValues = [];
						$multiPostKey = null;
					}
					// If the prefix "PROD_" is found, create an array within the json array.
					if (substr($var, 0, 5) == "PROD_" || substr($var, 0, 4) == "Add_") {
						$productsFound = true;
						if (empty($subArrayName)) {
							$subArrayName = str_replace("PROD_", '', $var);
							$subArrayName = str_replace("Add_", '', $subArrayName);
						} else {
							$componentsArray[$subArrayName] = $subArray;
							$subArray = array();
							$subArrayName = str_replace("PROD_", '', $var);
							$subArrayName = str_replace("Add_", '', $subArrayName);
						}
						if (substr($var, 0, 5) == "PROD_" || substr($var, 0, 4) == "Add_") {
							$removeProductName = true;
						}
						$componentsArray[$subArrayName] = array();
					} elseif (!is_null($multiPostKey) && str_contains($var, $multiPostKey)) {
						array_push($tempMultiPostValues, $val);
					} elseif (substr($var, 0, 6) == 'multi-') {
						if (count($tempMultiPostValues) > 0) {
							if (count($subArray) > 0) {
								$subArray[$multiPostKey] = $tempMultiPostValues;
							} else {
								$componentsArray[$multiPostKey] = $tempMultiPostValues;
							}
						}
						$multiPostKey = str_replace('multi-', '', $cleansedName);
						$tempMultiPostValues = array();
					} elseif (str_contains($var, $subArrayName) && $productsFound) {
						if (!empty($val)) {
							$subArray[$cleansedName] = $this->validateInput($val);
						}
					}
				}
			}

			// Ensures the last product is added to the components array.
			if (count($tempMultiPostValues) > 0) {
				if (count($subArray) > 0) {
					$subArray[$multiPostKey] = $tempMultiPostValues;
				} else {
					$componentsArray[$multiPostKey] = $tempMultiPostValues;
				}
			}
			if (count($subArray) > 0) {
				$componentsArray[$subArrayName] = $subArray;
			}

			$validated = json_encode($componentsArray, JSON_NUMERIC_CHECK);
		}

		return $validated;
	}

	/**
	 * Validates the inputs from a pillbox input.
	 * @deprecated
	 * @param string $pillBoxId The ID of the multi-select dropdown that corresponds to the pillbox element.
	 * @param array $data An array containing the pillbox elements.
	 */
	private function validatePillBox(string $pillBoxId, array &$data)
	{
		$validated = '';
		$pillBoxId .= '_';
		foreach ($data as $key => $val) {
			if (str_contains($key, $pillBoxId)) {
				$validated .= "$val`";
			}
		}
		return $validated;
	}

	/**
	 * Pass in all data from a form and it will validate multi-select dropdowns and format inputs to be regularly validated afterwards.
	 * @param array $data Form data 
	 * @return array The validated data.
	 */
	function dynamicPillBoxValidation($data)
	{
		$validatedPillBoxInputs = array();
		$multiFkData = null;
		$multiFkKey = null;

		foreach ($data as $key => &$input) {
			if (substr($key, 0, 6) === 'multi-') {
				if (is_null($multiFkKey)) {
					$multiFkKey = str_replace('multi-', '', $key);
					$multiFkData = '';
				} else if (str_contains($key, $multiFkKey)) {
					$multiFkData .= "$input;";
				} else {

					$validatedPillBoxInputs[$multiFkKey] = $multiFkData;
					$multiFkKey = str_replace('multi-', '', $key);
					$multiFkData = '';
				}
			} else {
				$validatedPillBoxInputs[$key] = $input;
			}
		}
		if ($multiFkData != '') {
			$validatedPillBoxInputs[$multiFkKey] = rtrim($multiFkData, ";");
		}
		return $validatedPillBoxInputs;
	}

	/**
	 * Takes a piece of data typically from a POST request and validates/cleanses it for use in data submission to a model.
	 * @param string $postVar The POST variable name to be validated.
	 * @param bool $addSingleQuote If true, strings are enclosed by single quotes. This is primarily used to aid SQL query syntax.
	 * @return mixed The validated data from a POST request.
	 */
	protected function validatePostInput(mixed $data): mixed
	{
		$validated = false;
		if (!is_null($data)) {
			$validated = $this->validateInput($data);
		}

		return $validated;
	}

	/**
	 * Takes a piece of data typically from a model's response and validates/cleanses it for use in a view.
	 * @param string $postVar The POST variable name to be validated.
	 * @param bool $addSingleQuote If true, strings are enclosed by single quotes. This is primarily used to aid SQL query syntax.
	 * @return mixed The validated data received from the model.
	 */
	protected function validatePostOutput(mixed $data, bool $addSingleQuote = true): mixed
	{
		$validated = false;
		if (!is_null($data)) {
			$validated = $this->validateOutput($data, $addSingleQuote);
		} else {
			$validated = null;
		}

		return $validated;
	}

	/**
	 * Saves the files as described in the $_FILES variable to the specified directory.
	 * The files should be one that is posted into the $_FILES variable.
	 * 
	 * @param array $fileData An array from $_FILES for the specified files to upload.
	 * @param string $targetDir The target directory to move the uploaded file to.
	 * @param array $allowedExtensions An array of file extensions that are to be allowed. If a file is uploaded with an extension that is not in this list, the file will be denied.
	 * @param int $sizeLimit The maximum allowed file size for the uploaded file in bytes. Default is 15MB.
	 * @return string|false If successful, the filename of the uploaded file is returned. Else, false.
	 */
	protected function saveFile(array $fileData, array $allowedExtensions, string $targetDir = 'res/uploads/', int $sizeLimit = 15000000): string | false
	{
		$saved = false;

		if (isset($fileData["name"])) {
			if (is_array($fileData['name'])) {
				for ($i = 0; $i < count($fileData['name']); $i++) {
					$file = array(
						'name' => $fileData['name'][$i],
						'full_path' => $fileData['full_path'][$i],
						'type' => $fileData['type'][$i],
						'tmp_name' => $fileData['tmp_name'][$i],
						'error' => $fileData['error'][$i],
						'size' => $fileData['size'][$i]
					);
					if (($upload = $this->uploadFile($file, $targetDir, $allowedExtensions, "", $sizeLimit)) !== false) {
						$saved .= "$upload;";
					}
				}
			} else {
				$saved = $this->uploadFile($fileData, $targetDir, $allowedExtensions, "", $sizeLimit);
			}
		} else {
			error_log('Cannot find files.');
		}

		return $saved;
	}

	/**
	 * Uploads the specified file as defined in the $_FILES array.
	 * 
	 * @param array $fileData An array from $_FILES for the specified files to upload.
	 * @param string $targetDir The target directory to move the uploaded file to.
	 * @param array $allowedExtensions An array of file extensions that are to be allowed. If a file is uploaded with an extension that is not in this list, the file will be denied.
	 * @param int $sizeLimit The maximum allowed file size for the uploaded file in bytes. Default is 15MB.
	 * @param string $NewName A new name for the file, do NOT include file extension
	 * @return string|false If successful, the filename of the uploaded file is returned. Else, false.
	 */
	protected function uploadFile(array $fileData, string $targetDir, array $allowedExtensions, string $NewName = "", int $sizeLimit = 15000000): string | false
	{
		$saved = false;

		$fileExtension = strtolower(pathinfo(basename($fileData['name']), PATHINFO_EXTENSION));
		if($NewName != "") $fileData['name'] = $NewName .".". $fileExtension;
		$target = $targetDir . $fileData['name'];
		if ($fileData["size"] <= $sizeLimit) {
			if (in_array($fileExtension, $allowedExtensions)) {
				if (!is_dir($targetDir)) {
					var_dump($targetDir);
					mkdir($targetDir);
				}
	
				if (move_uploaded_file($fileData['tmp_name'], $target)) {
					$saved = $fileData['name'];
				} else {
					error_log('Failed to upload file. File may not exist in temp directory. Temp dir: "' . ini_get('upload_tmp_dir') . '"');
				}
			} else {
				error_log('Uploaded file is of a non-permitted type.');
			}
		} else {
			error_log('Uploaded file exceeds specified limit.');
		}

		return $saved;
	}

	/**
	 * Cleanses the given string to remove all spaces from it, removes any potential SQL statements and artefacts and makes the beginning of each word uppercase
	 * based on the position of the spaces. Used for data that will be used to create new database objects including tables and columns.
	 * 
	 * @param string $text The string to be cleansed.
	 * @return string The formatted string.
	 */
	protected function formatNameForDatabase(string $text): string
	{
		$positions = array();
		$thisPos = 0;
		$prevPos = -1; // Used if there are multiple cases of the same character.
		preg_match_all('/[ ]./', $text, $matches, PREG_NO_ERROR);

		foreach ($matches as $match) {
			foreach ($match as $char) {
				$thisPos = strpos($text, $char);

				if ($thisPos == $prevPos) {
					$thisPos = strpos($text, $char, $prevPos + 1);
				} else {
					$prevPos = $thisPos;
				}
				array_push($positions, $thisPos);
			}
		}

		// Remove all spaces from the name
		$text = str_replace(" ", "", $text);
		// Loop through all identified indexes in the name where a space was found,
		// and convert the character to uppercase
		//temporarily disabled as causes issues with the new FriendlyName Editing
		// for ($i = 0; $i < count($positions); $i++) {
		// 	$capitalisedChar = strtoupper(substr($text, $positions[$i] - $i, 1));
		// 	$text = substr_replace($text, $capitalisedChar, $positions[$i] - $i, 1);
		// }

		$text = preg_replace("/[^A-Za-z0-9 ]/", '', $text);
		$text = $this->cleanseStringForDynamicSQL($text);
		// Capitalise the first character of the name
		$text = substr_replace($text, strtoupper(substr($text, 0, 1)), 0, 1);
		return $text;
	}

	/**
	 * Formats any data passed through so it is valid for use in an SQL query.
	 * @param mixed $data Data to be validated for use in an SQL query.
	 * @return string The validated data as a string.
	 */
	private function validateInput(mixed $data): string
	{
		$validatedInput = $data;
		if ($data instanceof \DateTime) {
			$validatedInput = date_format($data, 'Y-m-d H:i:s');
		} else if (!is_numeric($data) && !is_null($data)) {
			$data = str_replace('`', '', $data);
			// if ($data === "" || $data === "NULL") {
			// 	$validatedInput = "NULL";
			// } else 
			if ($data === "on" || $data === "true") {
				$validatedInput = "1";
			} elseif ($data === "false") {
				$validatedInput = "0";
			}
			// } else if (is_null($data) || $data === "") {
			// 	$validatedInput = "NULL";
		}

		return $validatedInput;
	}

	/**
	 * Validates data so it can be output to the user in a readable format.
	 * @param mixed $data Data to be validated for display to the user, typically in a View.
	 * @return string The validated data.
	 */
	private function validateOutput(mixed $data): string
	{
		$validatedInput = $data;

		// If the timestamp on the datetime is 0, only return the date.
		if ($data instanceof \DateTime) {
			if (date_format($data, 'H:i:s') == "00:00:00") {
				$validatedInput = date_format($data, 'Y-m-d');
			} else {
				$validatedInput = date_format($data, 'Y-m-d H:i:s');
			}
		} else if (!is_numeric($data) && !is_null($data)) {
			$data = str_replace('`', '', $data);
			if ($data === "" || $data === "NULL") {
				$validatedInput = "NULL";
			} else if ($data === "on" || $data === "true") {
				$validatedInput = "1";
			} else if ($data === "false") {
				$validatedInput = "0";
			}
		}

		return $validatedInput;
	}

	/**
	 * Formats a string from PascalCase to separated words.
	 * @param string $text A PascalCase string to be formatted into separate words.
	 * @return string The space-separated word derived from the given PascalCase string.
	 */
	protected function removePascalCase(string $text): string
	{
		$positions = array();
		$thisPos = 0;
		$prevPos = -1; // Used if there are multiple cases of thr same character.
		preg_match_all('/[ ]./', $text, $matches, PREG_NO_ERROR);

		foreach ($matches as $match) {
			foreach ($match as $char) {
				$thisPos = strpos($text, $char);

				if ($thisPos == $prevPos) {
					$thisPos = strpos($text, $char, $prevPos + 1);
				} else {
					$prevPos = $thisPos;
				}
				array_push($positions, $thisPos);
			}
		}

		// Remove all spaces from the name
		$text = str_replace(" ", "", $text);
		// Capitalise the first character of the name
		$text = substr_replace($text, strtoupper(substr($text, 0, 1)), 0, 1);

		// Loop through all identified indexes in the name where a space was found,
		// and convert the character to uppercase
		for ($i = 0; $i < count($positions); $i++) {
			$capitalisedChar = strtoupper(substr($text, $positions[$i] - $i, 1));
			$text = substr_replace($text, $capitalisedChar, $positions[$i] - $i, 1);
		}

		return $text;
	}

	/**
	 * Cleanses the given string to remove any values or characters that could be used to cause SQL Injection.
	 * This function should be used on all inputs that are being passed to stored procedures that generate dynamic SQL queries with its parameters.
	 * @param string $value The string to be cleansed.
	 * @return string
	 */
	private function cleanseStringForDynamicSQL(string $value): string
	{
		$cleansed = str_replace(["'", ';'], '', $value);

		return $cleansed;
	}
}
