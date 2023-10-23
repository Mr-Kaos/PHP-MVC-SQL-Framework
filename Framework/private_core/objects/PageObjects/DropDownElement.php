<?php

namespace Application\PageBuilder;

require_once('FormElement.php');

/**
 * Defines a dropdown element to be built in HTML
 */
class DropDownElement extends FormElement
{
	private array $options;
	private ?string $selected;
	private bool $disabled;
	private array $attributes;

	/**
	 * Sets up a dropdown element object.
	 * Checks if any options were given. If none were given, options can be added later via {@see DropdownElement/addOption()}, else the dropdown is automatically disabled.
	 * Also checks if a selected value was set for the dropdown. If a HTML attribute of 'value' or 'selected' is given, it sets the dropdown's value to the specified value, if it exists in its options. The 'selected' attribute takes precedence over 'value' if both are given.
	 */
	public function __construct(string $id, string $label, ?array &$options = array(), ?array $attributes = array(), string $styleName = null, string $labelStyle = null)
	{
		parent::__construct($id, $label, isset($attributes['required']) ? $attributes['required'] : false, $styleName, $labelStyle);

		if (is_null($options)) {
			$this->options = array();
		} else {
			$this->options = $options;
		}
		if (isset($attributes['selected'])) {
			$this->selected = isset($attributes['selected']) ? $attributes['selected'] : '';
		} else {
			$this->selected = isset($attributes['value']) ? $attributes['value'] : '';
		}
		$this->disabled = isset($attributes['disabled']) ? $attributes['disabled'] : false;
		if (is_null($attributes)) {
			$attributes = array();
		}
		$this->attributes = $attributes;
	}

	public function __destruct()
	{
		return "DropDownElement destroyed.";
	}

	/**
	 * Returns the number of options in the dropdown.
	 */
	public function getOptionCount(): int
	{
		return count($this->options);
	}

	/**
	 * Adds a new option to the dropdown list.
	 * If the name of the option being added already exists, the existing value will be overwritten.
	 * @param int $insertAt the index to insert the new array element at. if null, will insert it at the end of the array.
	 */
	public function addOption(string $name, mixed $value, int $insertAt = null, bool $selected = false): void
	{
		if (is_null($insertAt)) {
			$this->options[$name] = $value;
		} else {
			$this->options = array_slice($this->options, 0, $insertAt) + [$name => $value] + array_slice($this->options, $insertAt);
		}
		if ($selected) {
			$this->selected = &$this->options[$name];
		}
	}

	/**
	 * Changes the selected value of the dropdown list.
	 * Calling this function after building the dropdown has no effect on the dropdown returned by {@see DropdownElement/buildElement()}
	 * @param string $optionValue The value of the option to set as the selected dropdown option.
	 */
	public function setSelected(string $optionValue): void
	{
		foreach ($this->options as $option => $value) {
			if (is_array($value)) {
				$keys = array_keys($value);
				$option = $value[$keys[0]];
				$value = $value[$keys[1]];
				if ($option == $optionValue) {
					$this->selected = $option;
					break;
				}
			} elseif ($value == $optionValue) {
				$this->selected = $value;
				break;
			} elseif ($option == $optionValue) {
				$this->selected = $option;
				break;
			}
		}
	}

	/**
	 * Sets the dropdown to be disabled
	 */
	public function disabled(bool $disable)
	{
		$this->disabled = $disable;
	}

	/**
	 * Builds the dropdown element using the $options property.
	 */
	public function buildElement(): string
	{
		$element = $this->buildLabel($this->required);
		$attributes = "";
		foreach ($this->attributes as $attr => $val) {
			$attributes .= "$attr=$val ";
		}

		if (isset($this->options['New Item'])) {
			$attributes .= 'modal="Modal_' . $this->id . '"';
		}

		$element .= '<select id="' . $this->id . '" name="' . $this->id . '"' . $this->getClassAttribute() . $attributes;
		if (count($this->options) === 0) {
			$element .= 'disabled><option value="NULL">No options available</option>';
		} else {
			if ($this->disabled) {
				$element .= 'disabled>';
			} else if (!$this->required) {
				$element .= '><option value="NULL">Select an option</option>';
			} else {
				$element .= ">";
			}
			$isAssociative = $this->checkIsAssociative($this->options);

			foreach ($this->options as $option => &$value) {
				if (is_array($value)) {
					$keys = array_keys($value);
					$option = $value[$keys[0]];
					$value = $value[$keys[1]];
					if (!$isAssociative) {
						$temp = $value;
						$value = $option;
						$option = $temp;
					}
				} else {
					if (!$isAssociative) {
						$temp = $value;
						$value = $option;
						$option = $temp;
					}
				}

				$element .= '<option value="' . $value . '"';
				if (!empty($this->selected) && $value == $this->selected) {
					$element .= " selected";
				}
				$element .= '>' . $option . '</option>';
			}
		}
		$element .= '</select>';

		return $element;
	}

	/**
	 * Checks to see if the given array is an associative array, i.e. uses non-numeric keys.
	 */
	private function checkIsAssociative(array $array): bool
	{
		$isAssociative  = false;
		foreach ($array as $key => &$val) {
			if (!is_int($key)) {
				$isAssociative = true;
				break;
			}
		}
		return $isAssociative;
	}
}
