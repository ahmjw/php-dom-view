<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class View
{
	private $dom;
	private $content;
	private $scripts = array();
	private $styles = array();
	private $config = array();
	private $data = array();
	private $xpath;

	public $doCloning = true;

	public function __construct($content, array $data = array(), array $config = array())
	{
		$this->content = $content;
		$this->data = $data;
		$this->config = $config;
	}

	public function getOutput()
	{
		if (empty($this->content)) return;

		$body = $this->dom->getElementsByTagName('body')->item(0);
		$children = $body->childNodes;
		$content = '';
		$i = 0;
		if ($children) {
			foreach ($children as $child) {
				$content .= $child->ownerDocument->saveHTML($child);
			}
		}
		return html_entity_decode($content);
	}

	public function getData()
	{
		return $this->data;
	}

	public function getScripts()
	{
		return $this->scripts;
	}

	public function getStyles()
	{
		return $this->styles;
	}

	public function getContent($name)
	{
		$node = $this->dom->getElementById($name);
		if ($node) {
			$children = $node->childNodes;
			$content = '';
			foreach ($children as $child) {
				$content .= $child->ownerDocument->saveHTML($child);
			}
			return $content;
		}
		return $this->content;
	}

	public function parse()
	{
		if (empty($this->content)) return;

		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');

		$this->dom = new \DomDocument();
		@$this->dom->loadHTML($content);
		$this->xpath = new \DOMXPath($this->dom);

		$this->applyVisibility();

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

		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();
	}

	private function setElementContent($node, $value)
	{
		if ($node->tagName == 'input') {
			$node->setAttribute('value', $value);
		} else if ($node->tagName == 'a') {
			$node->setAttribute('href', $value);
		} else  {
			$node->nodeValue = htmlentities($value);
		}
	}

	private function parseToElement($key, $value)
	{
		$results = $this->xpath->query("//*[@c." . $key . "]");

		if ($results->length > 0) {
			// Get HTML
			$origin_node = $results->item(0);
			$parent = $origin_node->parentNode;
			unset($this->data[$key]);
			// Apply data
			foreach ($value as $key2 => $value2) {
				@$node = $origin_node->cloneNode(true);
				$node_id = 'c.' . $key . $key2;
				$node->setAttribute('id', $node_id);
				$node->setAttribute('rel', $key2);
				$parent->appendChild($node);

				if (isset($value2[0]) && is_array($value2[0])) {
					foreach ($value2[0] as $key3 => $value3) {
						$node->setAttribute($key3, $value3);
					}
				}
				
				if (is_array($value2)) {
					foreach ($value2 as $key3 => $value3) {
						$this->parseToNode($node_id, $key, $key2, $key3, $value3);
					}
				} else {
					$this->setElementContent($node, $value2);
				}
			}
			$parent->removeChild($origin_node);
		} else {
			$node = $this->dom->getElementById($key);
			if ($node && is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if (is_numeric($key2)) {
						$this->setElementContent($node, $value2);
					} else{
						$node->setAttribute($key2, $value2);
					}
				}
			}
		}
	}

	private function parseToNode($node_id, $node_name, $id, $key, $value)
	{
		$query = "//*[@c." . $node_name . "][@id='" . $node_id . "']/*[@c." . $key . "]";
		$results = $this->xpath->query($query);

		if ($results->length > 0) {
			$child_node = $results->item(0);
			$child_node->setAttribute('rel', $id);
			$child_node->setAttribute('id', 'c.' . $key . $id);

			if (is_array($value)) {
				foreach ($value as $key2 => $value2) {
					if ($key2 === 0) {
						$child_node->nodeValue = $value2;
					} else {
						$child_node->setAttribute($key2, $value2);
					}
				}
			} else {
				$this->setElementContent($child_node, $value);
			}
		}
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

	public function separateScript()
	{
		if (empty($this->content)) return;

		$items = array();
		$nodes = $this->dom->getElementsByTagName('script');
		foreach ($nodes as $node) {
			$this->scripts[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}
	}

	public function separateStyle()
	{
		if (empty($this->content)) return;

		$items = array();
		$nodes = $this->dom->getElementsByTagName('link');
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}

		$items = array();
		$nodes = $this->dom->getElementsByTagName('style');
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}
	}

	private function applyVisibility()
	{
	}
}