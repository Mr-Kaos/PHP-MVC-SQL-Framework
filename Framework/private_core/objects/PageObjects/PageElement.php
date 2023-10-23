<?php

namespace Application\PageBuilder;

/**
 * This script defines the class used to build page objects dynamically.
 */

abstract class PageElement
{
	protected ?string $id;
	protected ?string $styleName;

	public function __construct(string $id, string $styleName = null)
	{
		$this->styleName = $styleName;
		$this->id = $id;
	}

	public function __destruct()
	{
		return "PageElement destroyed.";
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * Generates the HTML class attribute for the specified CSS class name.
	 * If no class name is provided, it will attempt to use this class' style property.
	 */
	protected function getClassAttribute(string $class = null): string
	{
		$classAttribute = "";

		if (is_null($class)) {
			if (!is_null($this->styleName) && $this->styleName !== "") {
				$classAttribute = 'class="' . $this->styleName . '"';
			}
		} else {
			$classAttribute = 'class="' . $class . '"';
		}

		return $classAttribute;
	}

	/**
	 * Builds the container created by the class' functions.
	 * @return string - The built element.
	 */
	abstract public function buildElement(): string;
}
