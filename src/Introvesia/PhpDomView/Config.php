<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 9th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class Config
{
	private static $data = array();

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

	public static function getData($key = null)
	{
		if ($key !== null) {
			return self::$data[$key];
		}
		return self::$data;
	}
}