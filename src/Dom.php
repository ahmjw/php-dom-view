<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 7th 2018
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
class Dom
{
	/**
     * View or layout name
     *
     * @var string
     */
	protected $name = 'index';

	/**
     * View or layout content
     *
     * @var string
     */
	protected $content = '';

	/**
     * DOM object
     *
     * @var object \DOMDocument
     */
	protected $dom;

	/**
     * Head part
     *
     * @var object \DOMDocument
     */
	protected $head;

	/**
     * Body part
     *
     * @var object \DOMDocument
     */
	protected $body;

	/**
     * XPath object for DOM querying
     *
     * @var object \DOMXPath
     */
	protected $xpath;

	/**
     * Shared data for parsing
     *
     * @var array
     */
	protected $data = array();

	/**
     * List of separated scripts
     *
     * @var array
     */
	protected $scripts = array();

	/**
     * List of separated styles
     *
     * @var array
     */
	protected $styles = array();

	/**
     * Layout URL
     *
     * @var array
     */
	protected $layout_url;

	/**
     * Get the separated scripts
     *
     * @return array
     */
	public function getScripts()
	{
		return $this->scripts;
	}

	/**
     * Get the separated styles
     *
     * @return array
     */
	public function getStyles()
	{
		return $this->styles;
	}

	/**
     * Load the DOM object of layout or view
     *
     * @param string $dir_config_key The key of directory in the config
     */
	protected function loadDom($dir_config_key)
	{
		$layout_name = Config::getData('layout_name');
		if (!empty($layout_name)) {
			$this->name = $layout_name;
		}
		$this->layout_url = Config::getData('layout_url');
		$this->base_url = Config::getData('base_url');
		$dir = str_replace('/', DIRECTORY_SEPARATOR, Config::getData($dir_config_key)) . DIRECTORY_SEPARATOR;
		$path = $dir . $this->name . '.html';
		if (!file_exists($path)) {
			throw new \Exception('Failed to load file: ' . $path, 500);
		}
		$this->content = file_get_contents($path);
		$this->content = preg_replace_callback('/<c\.partial\sname="(.+)?"><\/c\.partial>/', function($match) use($dir) {
			$path = $dir . $match[1] . '.html';
			if (!file_exists($path)) {
				throw new \Exception('Failed to load file: ' . $path, 500);
			}
			return file_get_contents($path);
		}, $this->content);
		$content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
		$content = $this->replaceGlobalVars($content);
		$this->dom = new \DOMDocument();
		@$this->dom->loadHTML($content);
		$this->xpath = new \DOMXPath($this->dom);
		$this->head = $this->dom->getElementsByTagName('head')[0];
		$this->body = $this->dom->getElementsByTagName('body')[0];
	}

	/**
	 * Replace the formatted code in HTML with global shared data
	 *
	 * @param string $content The layout or view content
	 */
	private function replaceGlobalVars($content)
	{
		// The formatted code without dot prefix
		$pattern = '/\{\{([^\.].*?)\}\}/';
		$callback = function($match) {
			$var_name = $match[1];
			return isset($this->data[$var_name]) ? $this->data[$var_name] : null;
		};
		return preg_replace_callback($pattern, $callback, $content);
	}

	/**
	 * Replace the formatted code in HTML inside looping
	 *
	 * @param string $element The target element
	 * @param array $data The current data for element
	 */
	private function replaceLoopingVars($element, $data)
	{
		$content = urldecode($element->ownerDocument->saveHTML($element));
		$content = preg_replace('/(<.*?c\.)([a-zA-Z0-9_]+)(.*?>)/', '$1$2=""$3', $content);
		// The formatted code without dot prefix
		$pattern = '/\{\{\.(.*?)\}\}/';
		$callback = function($match) use($data) {
			$var_name = $match[1];
			return isset($data[$var_name]) ? $data[$var_name] : null;
		};
		$content = preg_replace_callback($pattern, $callback, $content);
		$this->setInnerHTML($element, $content);
	}

	/**
     * Apply all variables from shared data to nodes
     *
     */
	protected function applyVars()
	{
		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$this->parseToElement($key, $value);
			} else {
				$results = @$this->xpath->query("//*[@c." . $key . "]");
				if ($results->length > 0) {
					// Get HTML
					$node = $results->item(0);
					if ($node->hasAttribute('c.if')) {
						$expression = $node->getAttribute('c.if');
						$node->removeAttribute('c.if');
						$condition_control = new ConditionRenderer($expression, $this->data, $value);
						if (!$condition_control->getResult()) {
							$node->parentNode->removeChild($node);
						}
					}
					$node->removeAttribute('c.' . $key);
					$this->setElementContent($node, $value);
				}
			}
		}
	}

	/**
     * Set the content of a node
     *
     * @param object $node Target node
     * @param string|int|float $value Value for node
     */
	protected function setElementContent($node, $value)
	{
		if ($node->tagName == 'input') {
			$node->setAttribute('value', $value);
		} else if ($node->tagName == 'img') {
			$node->setAttribute('src', $value);
		} else if ($node->tagName == 'a') {
			$node->setAttribute('href', $value);
		} else if ($node->tagName == 'meta') {
			$node->setAttribute('content', $value);
		} else  {
			$node->nodeValue = htmlentities($value);
		}
	}

	/**
	 * Set HTML content of an element
	 * @param object $element Element node
	 * @param string $html New HTML code
	 */
	private function setInnerHTML($element, $html)
	{
	    $fragment = $element->ownerDocument->createDocumentFragment();
	    $fragment->appendXML($html);
	    while ($element->hasChildNodes())
	        $element->removeChild($element->firstChild);
	    @$element->appendChild($fragment);
	}

	/**
     * Parse each elements
     *
     * @param string $key Key name of the data
     * @param string $value Value of the data
     */
	protected function parseToElement($key, $value)
	{
		$results = $this->xpath->query("//*[@c." . $key . "]");

		if ($results->length > 0) {
			// Get HTML
			$origin_node = $results->item(0);
			$parent = $origin_node->parentNode;
			// Apply data
			foreach ($value as $key2 => $value2) {
				@$node = $origin_node->cloneNode(true);
				if ($node->hasAttribute('c.if')) {
					$expression = $node->getAttribute('c.if');
					$node->removeAttribute('c.if');
					$condition_control = new ConditionRenderer($expression, $this->data, $value2);
					if (!$condition_control->getResult()) {
						continue;
					}
				}

				$node_id = 'c.' . $key . $key2;
				$node->setAttribute('id', $node_id);
				$node->setAttribute('rel', $key2);
				$parent->appendChild($node);

				if (isset($value2[0]) && is_array($value2[0])) {
					foreach ($value2[0] as $key3 => $value3) {
						$node->setAttribute($key3, $value3);
						$node->removeAttribute('c.' . $key3);
					}
				}
				
				if (is_array($value2)) {
					$this->replaceLoopingVars($node, $value2);
					foreach ($value2 as $key3 => $value3) {
						$this->parseToNode($node_id, $key, $key2, $key3, $value3);
					}
				} else {
					$this->setElementContent($node, $value2);
				}
				$node->removeAttribute('c.' . $key);
			}
			$parent->removeChild($origin_node);
		}
	}

	/**
     * Parse to the nodes inside an element
     *
     * @param string $node_id ID of the element
     * @param string $node_name Name of the element
     * @param int $id ID for the element
     * @param string $key Key of the data for element
     * @param array|string|int $value Value of the data for the element
     */
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
			$child_node->removeAttribute('c.' . $key);
		}
	}

	/**
     * Separate and collect the scripts
     *
     */
	protected function separateScript()
	{
		$items = array();
		$nodes = @$this->xpath->query("//body//script");
		foreach ($nodes as $node) {
			$this->scripts[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}
	}

	/**
     * Separate and collect the styles
     *
     */
	protected function separateStyle()
	{
		$items = array();
		$nodes = @$this->xpath->query("//body//link");
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}

		$items = array();
		$nodes = @$this->xpath->query("//head//style");
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}

		$items = array();
		$nodes = @$this->xpath->query("//body//style");
		foreach ($nodes as $node) {
			$this->styles[] = $node;
			$items[] = $node;
		}

		foreach ($items as $node) {
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}
	}

	/**
     * Apply the visibility for non-tagged elements
     *
     */
	protected function applyVisibility()
	{
		$results = @$this->xpath->query("//*[@c.if]");
		if ($results->length > 0) {
			foreach ($results as $key => $node) {
				if ($node->hasAttribute('c.if')) {
					$expression = $node->getAttribute('c.if');
					$node->removeAttribute('c.if');
					$condition_control = new ConditionRenderer($expression, $this->data, null);
					if (!$condition_control->getResult()) {
						$node->parentNode->removeChild($node);
					}
				}
				$node->removeAttribute('c.if');
			}
		}
	}

	/**
     * Apply the URL to all elements
     *
     */
	protected function applyUrl()
	{
		// CSS
		$nodes = $this->dom->getElementsByTagName('link');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->layout_url . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
		// JS
		$nodes = $this->dom->getElementsByTagName('script');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->layout_url . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Image
		$nodes = $this->dom->getElementsByTagName('img');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('src');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->layout_url . '/' . trim($url, ':');
				$node->setAttribute('src', $url);
			}
		}
		// Anchor
		$nodes = $this->dom->getElementsByTagName('a');
		foreach ($nodes as $node) {
			$url = $node->getAttribute('href');
			if (strlen($url) > 0 && $url[0] == ':') {
				$url = $this->base_url . '/' . trim($url, ':');
				$node->setAttribute('href', $url);
			}
		}
	}
}