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

	public static function setData($data)
	{
		self::$data = $data;
	}

	public static function getData($key = null)
	{
		if ($key !== null) {
			return self::$data[$key];
		}
		return self::$data;
	}
}