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
	private $separate_assets = true;

	public function __construct($name, array $data = array())
	{
		$this->name = $name;
		$this->data = $data;
		$this->loadDom('view_dir');
	}

	public function setSeparateAssets($value)
	{
		$this->separate_assets = $value;
	}

	public function getLayoutName()
	{
		return $this->layout_name;
	}

	public function getOutput($tag = 'body')
	{
		if (empty($this->content)) return;

		$content = '';

		if ($tag == 'body' && !$this->separate_assets) {
			if ($this->head->childNodes->length > 0) {
				foreach ($this->head->childNodes as $child) {
					$content .= $child->ownerDocument->saveHTML($child);
				}
			}
		}

		if ($this->body->childNodes->length > 0) {
			foreach ($this->body->childNodes as $child) {
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
			if ($node->hasAttribute('layout')) {
				Config::setData('layout_name', $node->getAttribute('layout'));
			}
			if ($node->hasAttribute('separate-assets')) {
				$this->separate_assets = $node->getAttribute('separate-assets') == 'true';
			}
			$node->parentNode->removeChild($node);
		}

		$this->applyVars();
		$this->applyVisibility();
		$this->applyUrl();
		
		if ($this->separate_assets) {
			$this->separateStyle();
			$this->separateScript();
		}
	}
}