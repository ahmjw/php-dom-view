<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 9th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\PhpDomView;

class ConditionRenderer
{
	private $global_data = array();
	private $expression;
	private $result = false;

	public function __construct($expression, array $global_data, $current_data)
	{
		$this->expression = trim($expression);
		$this->global_data = $global_data;
		$this->current_data = $current_data;
		$this->parse();
	}

	public function getResult()
	{
		return $this->result;
	}

	private function parse()
	{
		if (preg_match('/^var\s*\(\s*(.+)\s*\)\s*(!?)=\s*(.+)$/', $this->expression, $match)) {
			$key = $match[1];
			$value = $match[3];
			if (preg_match('/^\.(.+)/', $key, $match2)) {
				$var_name = $match2[1];
				if ($match[2] == '!')
					$this->result = $this->current_data[$var_name] != $value;
				else
					$this->result = $this->current_data[$var_name] == $value;
			} else if (preg_match('/(\.)/', $key)) { // Global array variable
				$items = explode('.', $key);
				$current_data = $this->global_data;
				foreach ($items as $item) {
					if (!isset($current_data[$item])) {
						continue;
					}
					$current_data = $current_data[$item];
				}
				if ($match[2] == '!')
					$this->result = $current_data != $value;
				else
					$this->result = $current_data == $value;
			} else { // Global non-array variable
				if ($match[2] == '!')
					$this->result = isset($this->global_data[$key]) && $this->global_data[$key] != $value;
				else
					$this->result = isset($this->global_data[$key]) && $this->global_data[$key] == $value;
			}
		} else if (preg_match('/^(!?)\s*var\s*\(\s*(.+)\s*\)$/', $this->expression, $match)) {
			$operand_b = $match[2];
			if ($match[1] == '!')
				$this->result = !isset($this->global_data[$operand_b]);
			else
				$this->result = isset($this->global_data[$operand_b]);
		}
	}
}