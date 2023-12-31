<?php
namespace EasyMVC\PageBuilder;

/**
 * This script defines the class used to build pages dynamically.
 */

abstract class PageElementBuilder
{
	protected ?string $id;
	protected string $styleName;
	protected array $pageElements = array();
	private array $usedIds = array();

	public function __construct(?string $id, mixed $styleName)
	{
		if (is_null($styleName)) {
			$styleName = "";
		}
		$this->id = $id;
		$this->addId($id);
		$this->styleName = $styleName;
	}

	public function __destruct()
	{
		return "PageBuilder destroyed.";
	}

	/**
	 * Returns true if the Id is successfully added or if the id is null (does not need to be added.)
	 */
	protected function addId(string $id = null): bool
	{
		$idAdded = false;

		if (is_null($id)) {
			$idAdded = true;
		} else if (!$this->checkIdTaken($id)) {
			array_push($this->usedIds, $id);
			$idAdded = true;
		}

		return $idAdded;
	}

	/**
	 * Checks if the given Id is already taken or not.
	 */
	private function checkIdTaken(string $newId): bool
	{
		$idIsTaken = false;

		foreach ($this->usedIds as $id) {
			if ($newId === $id) {
				$idIsTaken = true;
				break;
			}
		}

		return $idIsTaken;
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
	 * Formats any data passed through so it valid for use in HTML.
	 * 
	 * @param mixed $data Data to be validated for use in an SQL query.
	 */
	protected function cleanseOutput(mixed $data): mixed
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
	 * Builds the container created by the class' functions
	 */
	abstract public function buildContainer(): string;
}
