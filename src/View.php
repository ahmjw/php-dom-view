<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 4th 2018
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
class View extends Dom
{
	/**
     * Layout name
     *
     * @var string
     */
	private $layout_name;

	/**
     * Use assets separation or not
     *
     * @var bool
     */
	private $separate_assets = true;

	/**
     * Class constructor
     *
     * @param string $name Name of view
     * @param array $data Shared data for parsing
     */
	public function __construct($name, array $data = array())
	{
		$this->name = $name;
		$this->data = $data;
		$this->loadDom('view_dir');
	}

	/**
     * Set the view use assets separation or not
     *
     * @param bool $uri Use assets separation or not
     */
	public function setSeparateAssets($value)
	{
		$this->separate_assets = $value;
	}

	/**
     * Get layout name of the view
     *
     * @return string
     */
	public function getLayoutName()
	{
		return $this->layout_name;
	}

	/**
     * Get the final output in head or body
     *
     * @param string $uri Tag name
     * @return string
     */
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

	/**
     * Parse the view
     *
     */
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