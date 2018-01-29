<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 9th 2018
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
class Config
{
	/**
     * Shared data for view and layout
     *
     * @var array
     */
	private static $data = array();

	/**
     * Set the shared configuration
     *
     * @param string $uri URI
     */
	public static function setData()
	{
		switch (func_num_args()) {
			case 1:
				self::$data = array_merge(self::$data, func_get_arg(0));
				break;
			case 2:
				self::$data[func_get_arg(0)] = func_get_arg(1);
				break;
		}
	}

	/**
     * Get all or specific data in configuration
     *
     * @param string $key The key of configuration
     * @return array|object|string|bool|numeric
     */
	public static function getData($key = null)
	{
		if ($key !== null) {
			return self::$data[$key];
		}
		return self::$data;
	}
}