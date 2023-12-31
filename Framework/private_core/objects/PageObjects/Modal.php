<?php

namespace EasyMVC\PageBuilder;

require_once('PageElement.php');
/**
 * This script defines the class used to build modal elements on a page.
 * 
 * Pages that utilise modal elements built from this class should include the ModalDisplay.js file to handle interactions with it.
 */

class Modal extends PageElement
{
	private string $title;
	private array $content;

	public function __construct(string $id, string $title, string $styleName = null)
	{
		parent::__construct($id, $styleName);
		$this->title = $title;
		$this->content = array();
	}

	public function __destruct()
	{
	}

	public function addContent(mixed $content)
	{
		array_push($this->content, $content);
	}

	public function buildElement(): string
	{
		$element = '<div id="' . $this->getId() . '" class="modal-bg hidden"><div class="modal-window"><div class="modal-title-bar"><h3>' . $this->title;
		$element .= '</h3><span class="modal-close" modalID="' . $this->getId() . '">&times;</span></div>';

		foreach ($this->content as $content) {
			$element .= $content;
		}
		$element .= '</div></div>';

		return $element;
	}
}
