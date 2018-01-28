<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class Layout extends Dom
{
	private $view;
	private $widget_keys = array();

	public function __construct($name, array $data)
	{
		$this->name = $name;
		$this->data = $data;
	}

	public function getWidgetKeys()
	{
		return array_keys($this->widget_keys);
	}

	public function getOutput()
	{
		if (empty($this->content)) return;

		$content = $this->dom->saveHTML();
		return html_entity_decode($content);
	}

	private function appendHtml(\DOMNode $parent, $content) 
	{
		$temp = new \DOMDocument();
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		@$temp->loadHTML($content);

		if ($temp->getElementsByTagName('body')->item(0)->childNodes) {
			foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
				$node = @$parent->ownerDocument->importNode($node, true);
				$parent->appendChild($node);
			}
		}
	}

	private function collectWidgetKeys()
	{
		// Collecting widget keys
		$nodes = $this->dom->getElementsByTagName('c.widget');
		foreach ($nodes as $node_import) {
			$name = $node_import->getAttribute('name');
			if (!array_search($name, $this->widget_keys)) {
				$this->widget_keys[$name] = $node_import;
			}
		}
	}

	public function renderWidget($key, $data)
	{
		$parent = $this->widget_keys[$key]->parentNode;
		foreach ($data as $dom) {
			$dom->parse();
			$element = $this->dom->createTextNode($dom->getOutput());
			$parent->insertBefore($element, $this->widget_keys[$key]);
		}
		$parent->removeChild($this->widget_keys[$key]);
	}

	public function parse($view = null)
	{
		$view->parse();
		$this->loadDom('layout_dir');
		$this->collectWidgetKeys();

		$this->applyVars();
		$this->applyVisibility();
		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();

		// Yield content
		$nodes = $this->dom->getElementsByTagName('c.content');
		$node = $nodes->item(0);
		if ($node) {
			if ($view)
				$this->renderView($node, $view);
			$node->parentNode->removeChild($node);
		}
	}

	private function renderView($node, $view)
	{
		$element = $this->dom->createTextNode($view->getOutput());
		$node->parentNode->insertBefore($element, $node);

		// Apply styles		
		foreach ($this->styles as $item_node) {
			$imported_node = $this->dom->importNode($item_node, true);
			$this->head->appendChild($imported_node);
		}

		// Apply scripts		
		foreach ($this->scripts as $item_node) {
			$imported_node = $this->dom->importNode($item_node, true);
			$this->body->appendChild($imported_node);
		}

		// View apply styles
		foreach ($view->getStyles() as $item_node) {
			$imported_node = $this->dom->importNode($item_node, true);
			$this->head->appendChild($imported_node);
		}

		// View apply scripts		
		foreach ($view->getScripts() as $item_node) {
			$imported_node = $this->dom->importNode($item_node, true);
			$this->body->appendChild($imported_node);
		}
	}
}