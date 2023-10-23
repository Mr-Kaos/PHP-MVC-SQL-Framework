<?php

namespace Application\PageBuilder;

use \Application\Model as m;

require_once("PageBuilder.php");

/**
 * Provides functions to create the TableCreator form on a webpage.
 * @property string $name The name of the tableCreator form. This will be displayed to the user in a heading.
 * @property bool $allowIdentifier determines if the Identifier field should be generated.
 * @property bool $dropdownWithSchema If true, the dropdowns will be generated with a schema in its value. Else, only the table name will be used.
 * @property ?array $prefillData An array obtained from a model that contains data to prefill the table with.
 */
class TableCreatorForm extends PageElementBuilder
{

	private string $name;
	private bool $allowIdentifier = false;
	private bool $dropdownWithSchema = false;
	private ?array $prefillData;

	public function __construct(string $name, bool $allowIdentifier = false, array $prefillData = null, bool $dropdownWithSchema = false, string $styleName = null)
	{
		parent::__construct(null, $styleName);
		$this->name = $name;
		$this->allowIdentifier = $allowIdentifier;
		$this->dropdownWithSchema = $dropdownWithSchema;
		$this->prefillData = $prefillData;
	}

	/**
	 * Uses the data given from the model to create a hidden HTML element that contains all necessary data to generate prefilled data in JavaScript.
	 * Is called by @see{buildContainer} if the property @see{TableCreatorForm/prefillData} is not null.
	 * If @see{TableCreatorForm/allowIdentifier} is true, overriding will be disabled. This is due to composite lists having identifiers but not having overridable properties.
	 * @author Kristian Oqueli Ambrose
	 * @return string
	 */
	private function generatePrefillDataContainer()
	{
		// Generate a HTML element to store the prefill data so the tableBuilder JS script can read it and prefill the fields.
		$formPrefill = '<div id="table_creator_prefill" class="hidden">';

		foreach ($this->prefillData[m\DATA_FORM_MAIN] as $field) {
			if (!is_null($field['PROPERTY_NAMES'])) {
				$properties = array_combine(explode("`", $field["PROPERTY_NAMES"]), explode("`", $field["PROPERTY_VALUES"]));
				if (isset($properties["UserGenerated"])) {
					$formPrefill .= '<span class="hidden"';
					$formPrefill .= 'fieldName="' . $properties["UserFriendlyName"] . '" ';
					$formPrefill .= 'allowNulls="' . $field['IS_NULLABLE'] . '" ';
					if (str_contains($field['PROPERTY_NAMES'], 'IsName')) {
						$formPrefill .= 'isIdentifier="1" ';
					}
					if (!$this->allowIdentifier) {
						$formPrefill .= 'allowOverride="' . $field['IS_ENTERED_BY_USER'] . '" ';
					}

					switch ($field['DATA_TYPE']) {
						case 'tinyint':
						case 'smallint':
						case 'int':
							if (isset($this->prefillData[m\DATA_DEPENDENCIES]['Prefill_Enums'][$field['FIELD_NAME']])) {
								$schema = $this->prefillData[m\DATA_DEPENDENCIES]['Prefill_Enums'][$field['FIELD_NAME']]['Schema'];
								$table = $this->prefillData[m\DATA_DEPENDENCIES]['Prefill_Enums'][$field['FIELD_NAME']]['Table'];
	
								if ($this->dropdownWithSchema) {
									$formPrefill .= 'fieldType="enum" ' . 'Value="' . "$schema.$table" . '"';
								} else {
									$formPrefill .= 'fieldType="enum" ' . 'Value="' . $table . '"';
								}
							}
							
							if ($field['DATA_TYPE'] == 'tinyint') {
								$formPrefill .= 'fieldType="number" Max="255"';
							
							} else if ($field['DATA_TYPE'] == 'smallint') {
								$formPrefill .= 'fieldType="number" Max="32767"';

							} else  if ($field['DATA_TYPE'] == 'int') {
								$formPrefill .= 'fieldType="number" Max="2147483647"';
							}
							break;
						case 'decimal':
							$max = '';
							$i = 0;
							while ($i < ($field['NUMERIC_PRECISION'] - $field['NUMERIC_SCALE'])) {
								$max .= '9';
								$i++;
							}
							$formPrefill .= 'fieldType="number" Max="' . $max . '" Decimal="' . $field['NUMERIC_SCALE'] . '"';
							break;
						case 'varchar':
							if (str_contains($field['PROPERTY_NAMES'], 'MultiForeignKey')) {
								$schema = $this->prefillData[m\DATA_DEPENDENCIES]['Prefill_Enums'][$field['FIELD_NAME']]['Schema'];
								$table = $this->prefillData[m\DATA_DEPENDENCIES]['Prefill_Enums'][$field['FIELD_NAME']]['Table'];
	
								if ($this->dropdownWithSchema) {
									$formPrefill .= 'fieldType="enum" ' . 'Value="' . "$schema.$table" . '"';
								} else {
									$formPrefill .= 'fieldType="enum" ' . 'Value="' . $table . '"';
								}
								
								$formPrefill .= ' multiFK="true"';
							} else {
								$formPrefill .= 'fieldType="text" Max="' . $field['CHARACTER_MAXIMUM_LENGTH'] . '"';
							}
							break;
						case 'nvarchar':
							$formPrefill .= 'fieldType="textUTF" Max="' . $field['CHARACTER_MAXIMUM_LENGTH'] . '"';
							break;
						case 'bit':
							$formPrefill .= 'fieldType="boolean"';
							break;
					}
				}
			}

			$formPrefill .= '></span>';
		}
		$formPrefill .= '</div>';
		return $formPrefill;
	}

	/**
	 * @author Lachlan Kearney
	 * @author Kristian Oqueli Ambrose
	 * Builds a HTML table for use with tableCreator.js to create a new database table.
	 * @param string $name The name of the type of table being created.
	 * @param bool $allowIdentifier If true, adds the Identifier column.
	 */
	public function buildContainer(): string
	{
		$content = '';
		if (!is_null($this->prefillData)) {
			$content = $this->generatePrefillDataContainer();
		}

		$content .= '<h2> ' . $this->name . ' Fields</h2><table><tbody id="attributesTable"><tr id="attributesTableHead">
			<th id="attr_table_name">Name</th>
			<th id="attr_table_type">Type</th>
			<th id="attr_table_value">Value</th>
			<th id="attr_table_required">Required</th>';
		if ($this->allowIdentifier) {
			$content .= '<th id="attr_table_identifier">Identifier?</th>';
		} else {
			$content .= '<th id="attr_table_override">Allow Override</th>';
		}
		$content .= '<th id="attr_table_remove"><button id="newAttribute" type="button" class="btn-icon btn-add" title="Add Field"></button></th></tr></tbody></table>';
		$content .= '<script defer src="res/js/TableCreator.js"></script>';
		$content .= '<div id="table-builder-alert-box" class="hidden alert-box-warning"></div>';
		return $content;
	}
}
