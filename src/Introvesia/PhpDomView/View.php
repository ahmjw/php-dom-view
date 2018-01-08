<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class View extends Dom
{
	private $content;
	private $scripts = array();
	private $styles = array();
	private $layout_name;

	public function __construct($content, array $data = array(), array $config = array())
	{
		$this->content = $content;
		$this->data = $data;
		$this->config = $config;
	}

	public function getLayoutName()
	{
		return $this->layout_name;
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

		// Read setting
		$nodes = $this->dom->getElementsByTagName('c.config');
		$node = $nodes->item(0);
		if ($node) {
			$this->layout_name = $node->getAttribute('layout');
			$parent = $node->parentNode;
			$parent->removeChild($node);
		}

		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();
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
}