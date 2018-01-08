<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 7th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class Dom
{
	protected $dom;
	protected $xpath;
	protected $config = array();
	protected $data = array();

	protected function setElementContent($node, $value)
	{
		if ($node->tagName == 'input') {
			$node->setAttribute('value', $value);
		} else if ($node->tagName == 'a') {
			$node->setAttribute('href', $value);
		} else if ($node->tagName == 'meta') {
			$node->setAttribute('content', $value);
		} else  {
			$node->nodeValue = htmlentities($value);
		}
	}

	protected function parseToElement($key, $value)
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

	protected function parseToNode($node_id, $node_name, $id, $key, $value)
	{
		$query = "//*[@c." . $node_name . "][@id='" . $node_id . "']//*[@c." . $key . "]";
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

	protected function applyVisibility()
	{
	}

	protected function replaceNode()
	{
	}

	protected function applyUrl()
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