<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */

namespace Introvesia\PhpDomView;

class Layout
{
	private $dom;
	private $content = '';
	private $config = array();
	private $data = array();
	private $scripts = array();
	private $xpath;

	public $doCloning = true;

	public function __construct(array $config, array $data)
	{
		$this->config = $config;
		$this->data = $data;
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

	public function parse()
	{
		$file_name = $this->config['path'] . $this->config['name'] . '.html';
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

		$this->applyVisibility();

		// Layout importing
		$nodes = $this->dom->getElementsByTagName('c.import');
		foreach ($nodes as $node) {
			$name = $this->config['path'] . DIRECTORY_SEPARATOR . $node->getAttribute('name') . '.html';
			if (file_exists($name)) {
				$content = file_get_contents($name);
				$dom = new View($content, array(), $this->config);
				$dom->parse();
				$element = $this->dom->createTextNode($dom->getOutput()); 
				$parent = $node->parentNode;
				$parent->insertBefore($element, $node);
			}
			$parent->removeChild($node);
		}

		// Yield content
		$nodes = $this->dom->getElementsByTagName('c.content');
		$node = $nodes->item(0);
		if ($node) {
			if (!file_exists($this->config['view'])) {
				throw new \Exception('View not found: ' . $this->config['view'], 500);
				
			}

			$content = file_get_contents($this->config['view']);
			$dom = new View($content, $this->data, $this->config);
			$dom->parse();
			$element = $this->dom->createTextNode($dom->getOutput());
			$view_parent = $node->parentNode;
			$view_parent->insertBefore($element, $node);
			$view_parent->removeChild($node);

			$head = $this->dom->getElementsByTagName('head')->item(0);
			$body = $this->dom->getElementsByTagName('body')->item(0);

			// Apply styles
			foreach ($dom->getStyles() as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$head->appendChild($imported_node);
			}

			// Apply scripts		
			foreach ($this->scripts as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$body->appendChild($imported_node);
			}

			// Apply scripts		
			foreach ($dom->getScripts() as $item_node) {
				$imported_node = $this->dom->importNode($item_node, true);
				$body->appendChild($imported_node);
			}
		}

		$this->applyUrl();
	}

	private function parseToElement($key, $value)
	{
		$results = $this->xpath->query("//*[@c." . $key . "]");

		if ($results->length > 0) {
			// Get HTML
			$node = $results->item(0);
			$parent = $node->parentNode;
			// Apply data
			foreach ($value as $key2 => $value2) {
				@$node->setAttribute('id', $key . $key2);
				$node->setAttribute('rel', $key2);

				if (isset($value2[0]) && is_array($value2[0])) {
					foreach ($value2[0] as $key3 => $value3) {
						$node->setAttribute($key3, $value3);
					}
				}
				
				if (is_array($value2)) {
					foreach ($value2 as $key3 => $value3) {
						$this->parseToNode($key2, $key3, $value3);
					}
				} else {
					$node->nodeValue = $value2;
				}

				if ($this->doCloning) {
					@$clone = $node->cloneNode(true);
					$parent->appendChild($clone);
				}
			}

			if ($this->doCloning) {
				$parent->removeChild($node);
			}
		} else {
			$node = $this->dom->getElementById($key);
			if ($node && is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if (is_numeric($key2)) {
						$node->nodeValue = $value2;
					} else{
						@$node->setAttribute($key2, $value2);
					}
				}
			}
		}
	}

	private function parseToNode($id, $key, $value)
	{
		$results = $this->xpath->query("//*[@class='" . $key . "']");

		if ($results->length > 0) {
			$node = $results->item(0);
			$node->setAttribute('rel', $id);
			@$node->setAttribute('id', $key . $id);

			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if ($key2 === 0) {
						@$node->nodeValue = $value2;
					} else {
						$node->setAttribute($key2, $value2);
					}
				}
			} else {
				$node->nodeValue = $value;
			}
		}
	}

	private function applyVisibility()
	{
	}

	private function replaceNode()
	{
	}

	private function applyUrl()
	{
		// CSS
		$nodes = $this->dom->getElementsByTagName('link');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
		// JS
		$nodes = $this->dom->getElementsByTagName('script');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Image
		$nodes = $this->dom->getElementsByTagName('img');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['layout_url'] . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Anchor
		$nodes = $this->dom->getElementsByTagName('a');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->config['base_url'] . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
	}
}