<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class View extends Dom
{
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

	public function getOutput($tag = 'body')
	{
		if (empty($this->content)) return;

		$body = $this->dom->getElementsByTagName($tag)->item(0);
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
			Config::setData('layout_name', $node->getAttribute('layout'));
			$node->parentNode->removeChild($node);
		}

		// View importing
		$nodes = $this->dom->getElementsByTagName('c.import');
		foreach ($nodes as $node_import) {
			$parent = $node_import->parentNode;
			$dom = new ViewPart($node_import->getAttribute('name'), array());
			$dom->parse();
			$element = $this->dom->createTextNode($dom->getOutput()); 
			$parent->appendChild($element);
		}

		$this->applyVars();
		$this->applyVisibility();
		$this->applyUrl();
		$this->separateStyle();
		$this->separateScript();
	}
}