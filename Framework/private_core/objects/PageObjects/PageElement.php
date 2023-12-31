<?php

namespace EasyMVC\PageBuilder;

/**
 * This script defines the class used to build page objects dynamically.
 */

abstract class PageElement
{
	protected ?string $id;
	protected ?string $styleName;
	protected array $attributes;

	public function __construct(string $id, ?array $attributes = [], string $styleName = null)
	{
		$this->id = $id;
		$this->attributes = is_null($attributes) ? [] : $attributes;
		$this->styleName = $styleName;
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
	 * Retrieves the value of the specified attribute if it exists.
	 * @param string $attributeName The name of the element's attribute to retrieve its value for.
	 * @return mixed The value of the attribute. If not found, returns null.
	 */
	public function getAttribute(string $attributeName): mixed
	{
		return isset($this->attributes[$attributeName]) ? $this->attributes[$attributeName] : null;
	}

	/**
	 * Checks if the specified attribute exists.
	 * @param string $attributeName The name of the element's attribute to retrieve its value for.
	 * @return bool True if the attribute exists.
	 */
	public function checkAttribute(string $attributeName): mixed
	{
		return isset($this->attributes[$attributeName]);
	}

	/**
	 * Adds the specified attribute to the element.
	 * @param string $name The name of the attribute to add to the input.
	 * @param string|int $value The value to be assigned to the attribute.
	 */
	public function addAttribute(string $name, string | int $value): void
	{
		$this->attributes[$name] = $value;
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
	 * @return string - The generated HTML of the element.
	 */
	abstract public function buildElement(): string;
}
