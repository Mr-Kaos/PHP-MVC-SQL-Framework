<?php

namespace Application\PageBuilder;

require_once("PageBuilder.php");
require_once("InputBuilder.php");
require_once("private_core/objects/PageObjects/DropDownElement.php");

/**
 * This script defines the class used to build html forms for pages dynamically.
 */
const PROPERTY_SELECT = "AllowCustomerSelect";
const PROPERTY_FRIENDLY_NAME = "UserFriendlyName";
const PROPERTY_USER_GEN = "UserGenerated";
const PROPERTY_MULTI_FK_SCHEMA = "MultiForeignKeySchema";
const PROPERTY_MULTI_FK_TABLE = "MultiForeignKeyTable";

/**
 * @author Kristian Oqueli Ambrose
 */
class FormBuilder extends PageElementBuilder
{
	use InputBuilder;
	private ?string $formName;
	private ?string $method;
	private ?string $action;

	public function __construct(string $id, mixed $styleName, ?string $formName, string $method = null, string $action = null)
	{
		parent::__construct($id, $styleName);
		$this->formName = $formName;
		$this->method = $method;
		$this->action = $action;
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
		$form = '<form id="' . $this->id . '" ' . $this->getClassAttribute() . $method . $action . '>';
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
			$field = '<div>' . $this->buildInput($label, $nameId, $type, $additionalAttributes, $styleLabel, $styleInput) . '</div>';
		} else {
			$field = "<div class='alert-box-warning'>WARNING: The element id of [$nameId] is already taken!</div>";
		}

		return $field;
	}

	/**
	 * @author Lachlan Kearney
	 * @param string $name the name for the pillbox input
	 * @param string $id the id for the pillbox input
	 * @param array $options the options for the pillbox input
	 * @param bool $required whether the pillbox input is required
	 * @param array existingSelection the existing selections for the pillbox input 
	 * @return string the html for the pillbox input
	 * 
	 * The array passed in with existing Selection options should be a 1D array listing the values of the options that should be selected
	 */
	public function createMultiForeignDropdown(string $name, string $id, ?array &$options, $required = false, array $existingSelection = null)
	{
		$dropdown = $this->createDropDown($name, 'multi-' . $id, $options, ["required" => $required])->buildElement();
		$pillBox = "<div id='pillbox-" . $id . "'class='pill-box'></div>";
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
		return $dropdown . $pillBox . $prefill;
	}
}
