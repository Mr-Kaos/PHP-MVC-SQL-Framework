<?php

namespace EasyMVC\PageBuilder;

require_once('PageElement.php');

enum InputTypes: string
{
	case button = 'button';
	case checkbox = "checkbox";
	case color = "color";
	case date = "date";
	case datetime = "datetime";
	case datetimelocal = "datetime-local";
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
	case list = 'list';
	case dropdown = 'dropdown';
}

/**
 * This script defines the class used to build page objects dynamically.
 */
class InputElement extends PageElement
{
	private ?string $label;
	private ?string $labelStyle;
	private InputTypes $type;
	protected ?string $name;

	public function __construct(string $label, string $id, InputTypes $type, ?array $attributes = [], string $styleName = null, string $labelStyle = null)
	{
		parent::__construct($id, $attributes, $styleName);
		$this->type = $type;
		$this->label = $label;
		$this->labelStyle = $labelStyle;
		$this->name = isset($attributes['name']) ? $attributes['name'] : $id;
	}

	public function __destruct()
	{
		return "PageElement destroyed.";
	}

	/** Builds a HTML label element.
	 * @param bool $required If true, an asterisk will be placed beside the label to indicate the field is required.
	 */
	protected function buildLabel(bool $required = false): string
	{
		$label = '';
		if ($this->type !== InputTypes::hidden) {
			$label = '<label for="' . $this->id . '"' . $this->getClassAttribute($this->labelStyle) . '>' . $this->label;
			if ($required) {
				$label .= ' *';
			}
			$label .= '</label>';
		}
		return $label;
	}

	/**
	 * Builds the container created by the class' functions
	 * @return String the HTML of the constructed element.
	 */
	public function buildElement(): string
	{
		$field = $this->buildLabel(isset($this->attributes['required']));
		$error = false;
		$attributes = "";

		if (!is_null($this->styleName)) {
			$attributes .= ' class="' . $this->styleName . '"';
		}

		// validate the $additionalAttributes parameter to prevent HTML errors.
		foreach ($this->attributes as $attribute => &$val) {
			if (is_bool($val) && $val) {
				$attributes .= ' ' . $attribute;
			} elseif ($val != false) {
				$attributes .= ' ' . $attribute . '="' . $val . '"';
			}
		}

		$value = isset($this->attributes['value']) ? $this->attributes['value'] : null;
		if($value==null &&array_key_exists("Value",$this->attributes))$value= $this->attributes['Value'];
		if (!$error) {
			switch ($this->type) {
				case InputTypes::button:
					$field = '<button id="' . $this->id . '" name="' . $this->name . '" type="button"' . "$attributes>$this->label</button>";
					break;
				case InputTypes::textarea:
					$field .= '<textarea id="' . $this->id . '" name="' . $this->name . '"' . "$attributes>$value</textarea>";
					break;
				case InputTypes::checkbox:
					// $checked = ($value == 1) ? 'true' : 'false';
					$field .= '<input id="hidden-' . $this->id . '" name="' . $this->name . '" type="hidden"' . $attributes . ' value="0">' . '<input id="' . $this->id . '" name="' . $this->name . '" type="' . $this->type->name . '"' . "$attributes>";
					break;
				case InputTypes::list:
					$field .= '<input id="hidden-' . $this->id . '" name="' . $this->name . '" type="hidden"' . $attributes . ' value="0">' . '<input id="' . $this->id . '" name="' . $this->name . '" type="' . $this->type->name . '"' . "$attributes>";
					break;
				case InputTypes::datetime:
					$field .= '<input id="' . $this->id . '" name="' . $this->name . '" type="datetime"' . $attributes.">";
					break;
				case InputTypes::radio:
					$field .= '<input id="' . $this->id . '" name="' . $this->name . '" type="radio"' . $attributes . ">";
					break;
				default:
					$field .= '<input id="' . $this->id . '" name="' . $this->name . '" type="' . $this->type->value . '"' . "$attributes>";
			}
		}

		return $field;
	}
}
