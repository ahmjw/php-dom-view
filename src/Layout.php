<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 *
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

/**
 * @package    Introvesia
 * @subpackage PhpDomView
 * @copyright  Copyright (c) 2016-2018 Introvesia (http://chupoo.introvesia.com)
 * @version    v1.0.4
 */
class Layout extends Dom
{
	/**
     * List of widget keys
     *
     * @var array
     */
	private $widget_keys = array();

	/**
     * Class constructor
     *
     * @param string $name Layout name
     * @param array $data Shared data for parsing
     */
	public function __construct($name, array $data)
	{
		$this->name = $name;
		$this->data = $data;
	}

	/**
     * Get widget keys
     *
     * @return array
     */
	public function getWidgetKeys()
	{
		return array_keys($this->widget_keys);
	}

	/**
     * Get final output of parsed layout
     *
     * @return string
     */
	public function getOutput()
	{
		if (empty($this->content)) return;

		$content = $this->dom->saveHTML();
		return html_entity_decode($content);
	}

	/**
     * Append elements by HTML code after a node
     *
     * @param object $parent Target node
     * @param string $content HTML code
     */
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

	/**
     * Collect widget keys from layout
     *
     */
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

	/**
     * Render widget to the layout
     *
     * @param string $key Widget key name
     * @param array $data List of widget
     */
	public function renderWidget($key, array $data)
	{
		$parent = $this->widget_keys[$key]->parentNode;
		foreach ($data as $dom) {
			$dom->parse();
			$element = $this->dom->createTextNode($dom->getOutput());
			$parent->insertBefore($element, $this->widget_keys[$key]);
		}
		$parent->removeChild($this->widget_keys[$key]);
	}

	/**
     * Parse layout
     *
     * @param object $view Current executed view
     */
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

	/**
     * Render widget's view to a node
     *
     * @param object $node Target node
     * @param object $view Node of widget's view
     */
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