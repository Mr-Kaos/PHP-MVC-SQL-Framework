<?php
namespace Application\PageBuilder;

use Application\PageBuilder as pb;

/**
 * This class contains functions that are used to create input fields in HTML.
 */

enum InputTypes: string
{
	case button = 'button';
	case checkbox = "checkbox";
	case color = "color";
	case date = "date";
	case datetime = "datetime";
	case email = "email";
	case file = "file";
	case hidden = "hidden";
	case image = "image";
	case month = "month";
	case number = "number";
	case password = "password";
	case radio = "radio";
	case range = "range";
	case reset = "reset";
	case search = "search";
	case submit = "submit";
	case tel = "tel";
	case text = "text";
	case time = "time";
	case url = "url";
	case week = "week";
	case textarea = "textarea";
}

trait InputBuilder
{
	/**
	 * Creates a new input field of the specified type and returns it.
	 * @param string $label The display name of the field to be used in the input's label. Not to be confused with the name attribute.
	 * @param string $id The DOM ID to be used in this fieldset. Must be unique. Is also used as the input element's name attribute.
	 * @param InputTypes $type The type of the input field. Must be one of the InputType attributes.
	 * @param string $value The value the field will hold. Can be null if no value is necessary.
	 * @param bool $required Determines if the field is required or not before the form can be submitted.
	 * @param array $inputAttributes Any additional attributes to be added to the input element. E.g. max length, number steps or date limitations. Should be an associative array with the key being the attribute.
	 * @param string $styleLabel CSS selector for the label element.
	 * @param string $styleInput CSS selector for the input element.
	 */
	protected function buildInput(string $label, string $id = null, InputTypes $type, array $inputAttributes = null, string $styleLabel = null, string $styleInput = null): string
	{
		$field = "";
		$error = false;

		if ($type !== pb\InputTypes::hidden) {
			if (!is_null($styleLabel)) {
				$styleLabel = ' class="' . $styleLabel . '"';
			} else {
				$styleLabel = "";
			}

			if (isset($inputAttributes['required']) && (strlen($label) > 0)) {
				$field .= '<label for="' . $id . '"' . $styleLabel . ' title="This field is required">' . $label . '*</label>';
			} else {
				$field .= '<label for="' . $id . '"' . $styleLabel . '>' . $label . '</label>';
			}
		}

		$attributes = "";

		if (!is_null($styleInput)) {
			$attributes .= ' class="' . $styleInput . '"';
		}

		// validate the $additionalAttributes parameter to prevent HTML errors.
		if (!is_null($inputAttributes)) {
			foreach ($inputAttributes as $attribute => &$val) {
				if (is_bool($val)) {
					$attributes .= ' ' . $attribute;
				} else {
					$attributes .= ' ' . $attribute . '="' . $val . '"';
				}
			}
		}

		$value = isset($inputAttributes['value']) ? $inputAttributes['value'] : null;

		if (!$error) {
			if ($type == pb\InputTypes::button) {
				$field = '<button id="' . $id . '" name="' . $id . '" type="button"' . "$attributes>$label</button>";
			} else if ($type == pb\InputTypes::textarea) {
				$field .= '<textarea id="' . $id . '" name="' . $id . '"' . "$attributes>$value</textarea>";
			}else if($type == pb\InputTypes::checkbox){
				$field .= '<input id="hidden-'.$id.'" name="' . $id . '" type="hidden"' . "$attributes value='0'>" . '<input id="' . $id . '" name="' . $id . '" type="' . $type->name . '"' . "$attributes>";
			}else {
				$field .= '<input id="' . $id . '" name="' . $id . '" type="' . $type->name . '"' . "$attributes>";
			}
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
	public function createDropDown(string $label, string $id, ?array &$options, array $attributes = null, string $styleInput = null, string $styleLabel = null): DropDownElement
	{
		$dropdown = new DropDownElement("", "");

		if ($this->addId($id)) {
			return $dropdown = new DropDownElement($id, $label, $options, $attributes, $styleInput, $styleLabel);
		}

		return $dropdown;
	}

	/**
	 * Reads and SQL Server data type and returns the enum equivalent.
	 * For use in form input fields.
	 * Data types that are typed the same in both HTML and SQL use the "default" case.
	 * Custom data types can also be added as a case to allow for non-standard SQL types.
	 * 
	 * @param string $SQLType The SQL data type name to be translated into its HTML form equivalent.
	 */
	protected function SQLToHTMLDataType(string $SQLType): InputTypes
	{
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
				$htmlType = "datetime";
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

		foreach (pb\InputTypes::cases() as $enum) {
			if ($enum->name == $htmlType) {
				$enumType = $enum;
			}
		}

		return $enumType;
	}
}
