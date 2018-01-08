<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class Layout extends Dom
{
	private $view_dom;
	private $content = '';
	private $scripts = array();
	private $widgets = array();

	public function __construct(array $config, array $data)
	{
		$this->config = $config;
		$this->data = $data;
		$this->loadDom();
	}

	public function getWidgetKeys()
	{
		return array_keys($this->widgets);
	}

	public function getOutput()
	{
		if (empty($this->content)) return;

		$content = $this->dom->saveHTML();
		return html_entity_decode($content);
	}

	public function getLayoutData()
	{
		if (empty($this->content)) return;

		$this->dom = new \DomDocument();
		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
		@$this->dom->loadHTML($content);

		$head = $this->dom->getElementsByTagName('head')->item(0);
		$body = $this->dom->getElementsByTagName('body')->item(0);
		
		return array(
			'head' => $head->ownerDocument->saveHTML($head),
			'body' => $body->ownerDocument->saveHTML($body),
		);
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

	private function loadDom()
	{
		$content = file_get_contents($this->config['view']);
		$this->view_dom = new View($content, $this->data, $this->config);
		$this->view_dom->parse();

		if ($this->view_dom->getLayoutName() !== null) {
			$this->config['name'] = $this->view_dom->getLayoutName();
		}

		$file_name = $this->config['path'] . DIRECTORY_SEPARATOR . $this->config['name'] . '.html';
		if (!file_exists($file_name)) {
			throw new \Exception('Layout file not found: ' . $file_name, 500);
		}

		$this->content = file_get_contents($file_name);
		if (empty($this->content)) {
			return;
		}

		$this->dom = new \DomDocument();
		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
		$content = $this->content;
		@$this->dom->loadHTML($content);
		$this->xpath = new \DOMXPath($this->dom);

		// Collecting widgets
		$nodes = $this->dom->getElementsByTagName('c.widget');
		foreach ($nodes as $node_import) {
			$name = $node_import->getAttribute('name');
			if (!array_search($name, $this->widgets)) {
				$this->widgets[$name] = $node_import;
			}
		}
	}

	public function parse()
	{
		// Apply vars
		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$this->parseToElement($key, $value);
			} else {
				$results = @$this->xpath->query("//*[@c." . $key . "]");

				if ($results->length > 0) {
					// Get HTML
					$node = $results->item(0);
					$this->setElementContent($node, $value);
				}
			}
		}

		$this->applyVisibility();

		// Layout importing
		$nodes = $this->dom->getElementsByTagName('c.import');
		foreach ($nodes as $node_import) {
			$name = $this->config['path'] . DIRECTORY_SEPARATOR . $node_import->getAttribute('name') . '.html';
			$parent = $node_import->parentNode;
			if (file_exists($name)) {
				$content = file_get_contents($name);
				$dom = new View($content, array(), $this->config);
				$dom->parse();
				$element = $this->dom->createTextNode($dom->getOutput()); 
				$parent->appendChild($element);
			}
		}

		// Yield content
		$nodes = $this->dom->getElementsByTagName('c.content');
		$node = $nodes->item(0);
		if ($node) {
			if (!file_exists($this->config['view'])) {
				throw new \Exception('View not found: ' . $this->config['view'], 500);
				
			}

			$element = $this->dom->createTextNode($this->view_dom->getOutput());
			$view_parent = $node->parentNode;
			$view_parent->insertBefore($element, $node);
			$view_parent->removeChild($node);

			$head = $this->dom->getElementsByTagName('head')->item(0);
			$body = $this->dom->getElementsByTagName('body')->item(0);

			// Apply styles
			foreach ($this->view_dom->getStyles() as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$head->appendChild($imported_node);
			}

			// Apply scripts		
			foreach ($this->scripts as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$body->appendChild($imported_node);
			}

			// Apply scripts		
			foreach ($this->view_dom->getScripts() as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$body->appendChild($imported_node);
			}
		}

		$this->applyUrl();
	}

	public function widget($key, $data)
	{
		foreach ($data as $widget) {
			if (file_exists($widget->name)) {
				$content = file_get_contents($widget->name);
				$dom = new View($content, $widget->data, $this->config);
				$dom->parse();
				$element = $this->dom->createTextNode($dom->getOutput());
				$this->widgets[$key]->appendChild($element);
			}
		}
	}
}