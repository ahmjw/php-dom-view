<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 27th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class ViewPart extends Dom
{
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
	}
}