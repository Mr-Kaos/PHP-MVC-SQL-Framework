<?php

namespace Application\PageBuilder;

require_once('PageElement.php');
/**
 * This script defines the class used to build page objects dynamically.
 */

abstract class FormElement extends PageElement
{
	protected ?string $label;
	protected ?string $labelStyle;
	protected bool $required;

	public function __construct(string $id, string $label, bool $required = false, string $styleName = null, string $labelStyle = null)
	{
		parent::__construct($id, $styleName);
		$this->required = $required;
		$this->label = $label;
		$this->labelStyle = $labelStyle;
	}

	public function __destruct()
	{
		return "PageElement destroyed.";
	}

	/** Builds a HTML label element.
	 * @param bool $required If true, an asterisk will be placed beside the label to indicate the field is required.
	 */
	protected function buildLabel(bool $required = false)
	{
		$label = '<label for="' . $this->id . '"' . $this->getClassAttribute($this->labelStyle) . '>' . $this->label;
		if ($required) {
			$label .= ' *';
		}
		$label .= '</label>';
		return $label;
	}

	/**
	 * Builds the container created by the class' functions
	 * @return String the HTML of the constructed element.
	 */
	abstract public function buildElement(): string;
}
