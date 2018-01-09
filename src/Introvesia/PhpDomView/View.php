<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class View extends Dom
{
	private $scripts = array();
	private $styles = array();
	private $layout_name;

	public function __construct($name, array $data = array())
	{
		$this->name = $name;
		$this->data = $data;
		$this->loadDom('view_dir');
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

	public function parse()
	{
		// Read setting
		$nodes = $this->dom->getElementsByTagName('c.config');
		$node = $nodes->item(0);
		if ($node) {
			$this->layout_name = $node->getAttribute('layout');
			$node->parentNode->removeChild($node);
		}

		$this->applyVars();
		$this->applyVisibility();
		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();
	}

	public function getScripts()
	{
		return $this->scripts;
	}

	public function getStyles()
	{
		return $this->styles;
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