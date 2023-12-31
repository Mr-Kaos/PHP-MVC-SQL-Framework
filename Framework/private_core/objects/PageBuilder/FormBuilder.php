<?php

namespace EasyMVC\PageBuilder;

use \EasyMVC\Model as m;

require_once("PageBuilder.php");
require_once("SQLToWebPage.php");
require_once("private_core/objects/PageObjects/InputElement.php");
require_once("private_core/objects/PageObjects/DropDownElement.php");

/**
 * @author Kristian Oqueli Ambrose
 * This script defines the class used to build html forms for pages dynamically.
 */
class FormBuilder extends PageElementBuilder implements SQLToWebPage
{
	private ?string $formName;
	private ?string $method;
	private ?string $action;
	private ?string $enctype;
	private ?array $attributes;

	public function __construct(string $id, mixed $styleName, ?string $formName, string $method = null, string $action = null, string $enctype = null, $attributes = null)
	{
		parent::__construct($id, $styleName);
		$this->formName = $formName;
		$this->method = $method;
		$this->action = $action;
		$this->enctype = $enctype;
		$this->attributes = $attributes;
	}

	/**
	 * @author Kristian Oqueli Ambrose
	 * Builds the container created by the class' functions
	 * @param ?string $submitButtonText If not null, sets the submit button's text to the given string. If null, does not create a submit button.
	 * @param ?string $resetButtonText If not null, sets the reset button's text to the given string. If null, does not create a reset button.
	 * @param ?string $onSubmitEvent If not null, assigns an onSubmit event to the submit button.
	 */
	public function buildContainer(?string $submitButtonText = "Submit", ?string $resetButtonText = "Reset", string $onSubmitEvent = null): string
	{
		$method = $this->method ? ' method="' . $this->method . '"' : "";
		$action = $this->action ? ' action="' . $this->action . '"' : "";
		$enctype = $this->enctype ? ' enctype="' . $this->enctype . '"' : "";
		$attributes = "";
		if ($this->attributes != null) {
			foreach ($this->attributes as $attribute => &$val) {
				if (is_bool($val)) {
					$attributes .= ' ' . $attribute;
				} else {
					$attributes .= ' ' . $attribute . '="' . $val . '"';
				}
			}
		}

		$form = '<form id="' . $this->id . '" ' . $this->getClassAttribute() . $method . $action . $enctype . $attributes . '>';
		if (!is_null($this->formName)) {
			$form .= '<h3>' . $this->formName . '</h3>';
		}

		foreach ($this->pageElements as $element) {
			$form .= $element;
		}

		if (!is_null($resetButtonText)) {
			$form .= '<button id="' . $this->id . 'Reset" type="reset">' . $resetButtonText . '</button>';
		}
		if (!is_null($submitButtonText)) {
			$form .= '<button id="' . $this->id . 'Submit" type="submit"';
			if (!is_null($onSubmitEvent)) {
				$form .= 'onclick="' . $onSubmitEvent . '"';
			}
			$form .= '>' . $submitButtonText . '</button>';
		}
		$form .= '</form>';

		return $form;
	}

	/**
	 * @author Kristian Oqueli Ambrose
	 * Adds the passed item to the object's element array.
	 */
	public function addItemToPageBuilder(string $item): void
	{
		array_push($this->pageElements, $item);
	}

	/**
	 * @author Kristian Oqueli Ambrose
	 * Creates a new fieldset object with the given content and adds it to the PageBuilder's pageElements array.
	 * @param string $content The content to be added into the fieldset.
	 * @param mixed $legend The title of the fieldset found in the Legend HTML tag. If null, no legend attribute will be created.
	 * @param string $id The DOM ID to be used in this fieldset. Must be unique.
	 * @param mixed $styleName The name of the CSS class to be used to style the fieldset. If Null, no style will be used.
	 * @return string the fieldSet.
	 */
	public function addFieldSet(string $content, ?string $legend, string $id = null, string $styleName = null, bool $addToPageBuilder = true): string
	{
		$fieldset = "";

		if (!is_null($styleName)) {
			$styleName = ' class="' . $styleName . '"';
		}

		$fieldset = '<fieldset';

		if (!is_null($id)) {
			if ($this->addId($id)) {
				$fieldset .= ' id="' . $id . '"';
			} else {
				echo "<div class='alert-box-warning'>WARNING: The element id of [$id] is already taken!<div>";
			}
		}

		$fieldset .= $styleName . '>';

		if (!is_null($legend)) {
			$fieldset .= '<legend>' . $legend . '</legend>';
		}
		$fieldset .= $content . '</fieldset>';
		if ($addToPageBuilder) {
			array_push($this->pageElements, $fieldset);
		}

		return $fieldset;
	}

	/**
	 * @author Kristian Oqueli Ambrose
	 * Creates a new input field of the specified type and returns it.
	 * @param string $label The display name of the field to be used in the input's label. Not to be confused with the name attribute.
	 * @param string $nameId The DOM ID to be used in this fieldset. Must be unique. Is also used as the input element's name attribute.
	 * @param InputTypes $type The type of the input field. Must be one of the InputType attributes.
	 * @param string $additionalAttributes Any additional attributes to be added to the input element. E.g. max length, number steps or date limitations.
	 * @param string $styleLabel CSS selector for the label element.
	 * @param string $styleInput CSS selector for the input element.
	 */
	public function createInput(string $label, string $nameId = null, InputTypes $type, array $additionalAttributes = null, $styleLabel = null, string $styleInput = null): string
	{
		$field = "";

		if ($this->addId($nameId)) {
			$input = new InputElement($label, $nameId, $type, $additionalAttributes, $styleInput, $styleLabel);
			$field = $input->buildElement();
		} else {
			$field = "<div class='alert-box-warning'>WARNING: The element id of [$nameId] is already taken!</div>";
		}

		return $field;
	}

	/**
	 * Creates a HTML dropdown element. Creates select options based on the options passed.
	 * @param string $label The display name of the field to be used in the input's label. Not to be confused with the name attribute.
	 * @param string $id The DOM ID to be used in this fieldset. Must be unique. Is also used as the input element's name attribute.
	 * @param array $options The items to appear in the dropdown list. If the array is associative, the key will be the name displayed to the user and the value the value of the option. If the array is not associative, each option will have a numerical value ascending from 0.
	 * @param mixed $value The value the field will hold. Can be null if no value is necessary.
	 * @param mixed $additionalAttributes Any additional attributes to be added to the input element. E.g. max length, number steps or date limitations.
	 */
	public function createDropDown(string $label, string $id, ?array $options, array $attributes = null, string $styleInput = null, string $styleLabel = null): DropDownElement
	{
		$dropdown = new DropDownElement("", "");

		if ($this->addId($id)) {
			return $dropdown = new DropDownElement($label, $id, $options, $attributes, $styleInput, $styleLabel);
		} else {
			echo "<div class='alert-box-warning'>WARNING: The element id of [$id] is already taken!</div>";
		}

		return $dropdown;
	}

	/**
	 * @author Kristian Oqueli Ambrose
	 * Takes a resultset from view vw and parses each entry in it to generate form fields and inputs.
	 * From the identified columns or parameters, it generates form elements. from this information.
	 * @param resource $conn The SQLSRV connection resource used to perform the query.
	 * @param string $objectName The name of the table, view or stored procedure to create the form out of.
	 * @param string $schemaName The name of the $objectName's schema. Can be left null, but is recommended against.
	 * @param array $options Additional params for extra options to filter out the resultset used to create page elements. The following options are accepted:
	 * 				- ExcludedFields {array} - A list of fields (as an array) to be ignored from the resultset
	 * 				- UseObjectNameId {bool} - If true, will append the database object name to the id to reduce redundant IDs.
	 *				- OPTION_FIELDNAME_PREFIX {string} prefixes each input id and name attribute with the given string.
	 *				- OPTION_ALLOWWILDCARD {bool} If true, any drop-down lists will contain an asterisk (wildcard) option.
	 *				- OPTION_INCLUDE_PRIMARYKEY {bool} If true, will include the primary key field as an input field.
	 *				- OPTION_SHOW_DISALLOWED_FIELDS {bool} if true, any fields that have the property "IS_ENTERED_BY_USER" set to 0 will be visible
	 *				- OPTION_PREFILL_DATA {array} If given, the data in this array should contain the keys to 
	 *
	 * @param string $fieldIDPrefix A prefix to append to the field's ID and name. Used to help prevent duplicate IDs being generated in the page.
	 * @return string The constructed page elements. Returns Null if the $resultSet parameter is invalid.
	 */
	public function databaseObjectToPageElements(array $resultSet, array $foreignKeyDependencies = null, array $options = null, string $fieldIDPrefix = null): string
	{
		$field = "";
		$previousColumnName = "";
		$ignorePK = isset($options[OPTION_INCLUDE_PRIMARYKEY]) ? $options[OPTION_INCLUDE_PRIMARYKEY] : null;

		$showHiddenFields = isset($options[OPTION_SHOW_DISALLOWED_FIELDS]) ? $options[OPTION_SHOW_DISALLOWED_FIELDS] : false;
		// if (is_null($showHiddenFields = isset($options[OPTION_SHOW_DISALLOWED_FIELDS]) ? $options[OPTION_SHOW_DISALLOWED_FIELDS] : null)) {
		// 	echo 'sdsdsd';
		// 	$showHiddenFields = false;
		// }

		foreach ($resultSet as &$row) {
			if (isset($row["FIELD_NAME"])) {
				$fieldAttributes = array();
				// $paramName = str_replace('@', "", $row["FIELD_NAME"]);
				$labelName = str_replace('@', "", $row["FIELD_NAME"]);
				$displayField = true;
				if ($row["IS_ENTERED_BY_USER"] === 0 && !$showHiddenFields) {
					$displayField = false;
				}

				if (!$ignorePK && $row["PRIMARY_KEY"] !== 1 && $displayField) {
					$fieldId = is_null($fieldIDPrefix) ? $labelName : $fieldIDPrefix . "_" . $labelName;

					$fieldName = $row["FIELD_NAME"];
					if ($fieldName !== $previousColumnName) {
						$previousColumnName = $fieldName;
						$isPillBox = false;
						$required = false;
						$prefillValue = null;
						if (isset($options[OPTION_PREFILL_DATA])) {
							$prefillValue = $options[OPTION_PREFILL_DATA][$labelName];
							$fieldAttributes['value'] = $prefillValue;
						}
						// If a property exists, additional elements may be added to the end of the field.
						$fieldSuffix = null;
						$field .= '<div class="form-input-wrapper">';

						// Set the maxlength of the input field
						if (is_null($row['REFERENCED_SCHEMA_NAME'])) {
							$max = null;
							$min = 0;
							$step = "1";
							if (!is_null($row["MAX_LENGTH"]) && str_contains($row['DATA_TYPE'], 'char')) {
								$fieldAttributes["maxlength"] = $row["MAX_LENGTH"];
							} else if ($row["DATA_TYPE"] === "money") {
								$max = 922337203685477;
								$step = 0.01;
							} else if ($row["DATA_TYPE"] === "smallint") {
								$max = 32767;
							} else if ($row["DATA_TYPE"] === "tinyint") {
								$max = 255;
							} else if (!is_null($row["PRECISION"])) {
								$precision = $row["PRECISION"];

								if (!is_null($row["SCALE"]) && $row["SCALE"] !== 0) {
									$precision -= $row["SCALE"];
									$step = "0.";
									for ($j = 0; $j < $row["SCALE"] - 1; $j++) {
										$step .= "0";
									}
									$step .= "1";
								}

								if ($precision == 10) {
									$max = "2147483647";
								} else {
									$max = null;
									for ($j = 0; $j < $precision; $j++) {
										$max .= 9;
									}
								}
							} else if (substr($row["DATA_TYPE"], 0, 8) === "datetime-local") {
								$min = date('Y-m-d');
							}

							if (!is_null($max)) {
								$fieldAttributes["min"] = $min;
								$fieldAttributes["max"] = $max;
								$fieldAttributes["step"] = $step;
							}
						}

						if ($row["IS_NULLABLE"] === 0 && $row["DATA_TYPE"] !== "bit") {
							$fieldAttributes["required"] = true;
							$required = true;
						}
						if (!$displayField) {
							$row["DATA_TYPE"] = "hidden";
						}

						$inputStyle = null;

						// Check if extended properties exist for the current field.
						if ($row["PROPERTY_NAMES"] != null) {
							if ($row["PROPERTY_VALUES"] != null) {
								$properties = array_combine(explode("`", $row["PROPERTY_NAMES"]), explode("`", $row["PROPERTY_VALUES"]));

								$labelName = isset($properties['UserFriendlyName']) ? $properties['UserFriendlyName'] : $labelName;
								if (isset($properties['NumericLimit'])) {
									$fieldAttributes['max'] = $properties['NumericLimit'];
								}

								if (isset($properties['AllowCustomerSelect'])) {
									if ($properties['AllowCustomerSelect'] == 0 && $showHiddenFields) {
										$fieldAttributes['data-toggleDisplay'] = 'true';
									}
								}
								if (isset($properties['MultiForeignKeySchema'])) {
									$isPillBox = true;
									$prefillValue = null;
									if (isset($options[OPTION_PREFILL_DATA])) {
										$prefillValue = isset($options[OPTION_PREFILL_DATA][$row['FIELD_NAME']]) ? str_getcsv($options[OPTION_PREFILL_DATA][$row['FIELD_NAME']], ';') : null;
									}
									$field .= $this->createMultiForeignDropdown("$labelName Options", "$fieldId", $foreignKeyDependencies[m\DATA_DROPDOWNS][$row['FIELD_NAME']], $fieldAttributes, $prefillValue);
								}
							}
						}

						// If the column has a foreign key, obtain the dependencies of this key and create a drop-down for it.
						if (!is_null($row['REFERENCED_SCHEMA_NAME'])) {
							$referencedDropdown = &$foreignKeyDependencies[m\DATA_DROPDOWNS][$row["FIELD_NAME"]];
							if (!is_null($referencedDropdown)) {
								if ($this->addId($fieldId)) {
									if ($required) {
										$fieldAttributes['modal'] = "Modal_$fieldId";
									}
									$dropdown = new DropDownElement($labelName, $fieldId, $referencedDropdown, $fieldAttributes, $inputStyle, $inputStyle);
									if ($required) {
										if ($dropdown->getOptionCount() == 0) {
											$dropdown->addOption('Select an option', 'NULL');
										}
										$dropdown->addOption('New Item', 'NEW_ITEM');
									}
									$field .= $dropdown->buildElement();
								}
							} else {
								$field .= "<span>$labelName</span>" . '<span class="input-alert">An error occurred obtaining this dropdown.</span>';
							}
						}

						// // If a stored procedure contains extended properties that match the parameter names, it may be a foreign key reference.
						// else if ($row["KEY_TYPE"] == "FOREIGN KEY") {
						// 	// $dropdown = $this->createDropDown($labelName, $fieldId, $foreignKeyDependencies[m\DATA_DROPDOWNS][$paramName], $required, $inputStyle, $inputStyle, false, $fieldAttributes);
						// 	$dropdown = $this->createDropDown($labelName, $fieldId, $foreignKeyDependencies[m\DATA_DROPDOWNS][$paramName], $fieldAttributes, $inputStyle, $inputStyle);
						// 	if (isset($options[OPTION_ALLOW_WILDCARD])) {
						// 		$dropdown->addOption("Any", "*", 0);
						// 	}
						// 	if ($required) {
						// 		if ($dropdown->getOptionCount() == 0) {
						// 			$dropdown->addOption('Select an option', 'NULL');
						// 		}
						// 		$dropdown->addOption('New Item', 'NEW_ITEM');
						// 	}
						// 	if (!is_null($prefillValue)) {
						// 		$dropdown->setSelected($prefillValue);
						// 	}
						// 	$field .= $dropdown->buildElement();
						// } else 
						else if (!$isPillBox && $this->SQLToHTMLDataType($row["DATA_TYPE"]) == InputTypes::text && ($row["MAX_LENGTH"] > 100 || $row["MAX_LENGTH"] == -1)) {
							$field .= $this->createInput($labelName, $fieldId, InputTypes::textarea, $fieldAttributes, $inputStyle, $inputStyle);
						} else if (!$isPillBox && $this->SQLToHTMLDataType($row["DATA_TYPE"]) == InputTypes::checkbox) {
							if ($prefillValue == 1) $fieldAttributes["checked"] = "";
							$field .= $this->createInput($labelName, $fieldId, $this->SQLToHTMLDataType($row["DATA_TYPE"]), $fieldAttributes, $inputStyle, $inputStyle);
						} else if (!$isPillBox) {
							$field .= $this->createInput($labelName, $fieldId, $this->SQLToHTMLDataType($row["DATA_TYPE"]), $fieldAttributes, $inputStyle, $inputStyle);
						}
						$field .= $fieldSuffix . '</div>';
					}
				}
			}
		}
		return $field;
	}

	/**
	 * @author Lachlan Kearney
	 * @param string $name the name for the pillbox input
	 * @param string $id the id for the pillbox input
	 * @param array $input the input to give values to the multi-input, defaults to text input if null
	 * @param array $attributes Any attributes to apply to the dropdown input. 
	 * @param array existingSelection the existing selections for the pillbox input 
	 * @return string the html for the pillbox input
	 * 
	 * The array passed in with existing Selection options should be a 1D array listing the values of the options that should be selected
	 */
	public function createMultiInput(string $name, string $id, string $input = null,  array $attributes = null, array $existingSelection = null, $appendHTMLEnd = null, $appendHTMLStart = null)
	{
		if ($input == null) {
			$input = "<label for='multi-" . $id . "'>" . $name . "</label> <input onload='stopSubmitOnEnter(this)' id='multi-" . $id . "' name='multi-" . $id . "' > <button type='button' onclick='addMultiCustomEnteredOption(\"multi-" . $id . "\")'>Add Item</button>";
		}

		$pillBox = '<div id="pillbox-' . $id . '" class="pill-box"></div>';
		$prefill = "";
		if ($existingSelection != null) {
			$prefill = '<div class="hidden" id="prefill_' . $id . '">';
			foreach ($existingSelection as $val) {
				if ($val != "") {
					$prefill .= '<span>' . $val . '</span>';
				}
			}
			$prefill .= '</div>';
		}
		$appendHTMLInput = "";
		if ($appendHTMLEnd != null) {
			$appendHTMLInput = '<span class="hidden" id="' . $id . '-pill-html-append-back"> ' . $appendHTMLEnd . '</span>';
			$appendHTMLInput .= '<span class="hidden" id="' . $id . '-pill-html-append-front"> ' . $appendHTMLStart . '</span>';
		}

		return "<fieldset><legend>" . $name . "</legend>" . $input . $pillBox . $prefill . $appendHTMLInput . "</fieldset>";
	}

	/**			
	 * @author Lachlan Kearney
	 * @param string $name the name for the pillbox input
	 * @param string $id the id for the pillbox input
	 * @param array $options the options for the pillbox input - if null dropdown is replaced with text input
	 * @param array $attributes Any attributes to apply to the dropdown input. 
	 * @param array existingSelection the existing selections for the pillbox input 
	 * @return string the html for the pillbox input
	 * 
	 * The array passed in with existing Selection options should be a 1D array listing the values of the options that should be selected
	 */
	public function createMultiForeignDropdown(string $name, string $id, ?array &$options, array $attributes = null, array $existingSelection = null, $appendHTMLEnd = null, $appendHTMLStart = null)
	{
		$input = new DropDownElement($name, 'multi-' . $id, $options, $attributes);
		$input = $input->buildElement();
		$output = $this->createMultiInput($name, $id, $input, $attributes, $existingSelection, $appendHTMLEnd, $appendHTMLStart);
		return $output;
	}
	/**
	 * Reads and SQL Server data type and returns the enum equivalent.
	 * For use in form input fields.
	 * Data types that are typed the same in both HTML and SQL use the "default" case.
	 * Custom data types can also be added as a case to allow for non-standard SQL types.
	 * 
	 * @param string $SQLType The SQL data type name to be translated into its HTML form equivalent.
	 */
	private function SQLToHTMLDataType(string $SQLType): InputTypes
	{
		$SQLType = strtolower($SQLType);

		$htmlType = "text";
		$enumType = null;
		switch ($SQLType) {
			case "tinyint":
			case "int":
			case "smallint":
			case "bigint":
			case "decimal":
			case "numeric":
			case "money":
			case "float":
				$htmlType = "number";
				break;
			case "bit":
				$htmlType = "checkbox";
				break;
			case "date":
				$htmlType = "date";
				break;
			case "datetime":
			case "datetime2":
				$htmlType = "datetime-local";
				break;
			case "char":
			case "nchar":
			case "varchar":
			case "nvarchar":
				$htmlType = "text";
				break;
			case "phone":
				$htmlType = "tel";
				break;
			default:
				$htmlType = $SQLType;
				break;
		}

		foreach (InputTypes::cases() as $enum) {
			if ($enum->name == $htmlType) {
				$enumType = $enum;
			}
		}

		return $enumType;
	}
}
