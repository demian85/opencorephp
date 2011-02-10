<?php

// namespace util;

/**
 * TODO finish
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 * @package util
 */
class Arrays {
	
	private function __construct() { }

	/**
	 * Build array with values extracted from $haystack
	 * @param array[] $haystack
	 * @param string $key key to look for
	 * @return array
	 */
	static function collect(array $haystack, $key)
	{
		$data = array();
		if (is_array($key)) {
			foreach ($haystack as $row) {
				$_v = array();
				foreach($key as $k) {
					$_v[] = isset($row[$k]) ? $row[$k] : null;
				}
				$data[] = $_v;	
			}
		}
		else {
			foreach ($haystack as $row) {
				$data[] = isset($row[$key]) ? $row[$key] : null;	
			}
		}
		return $data;
	}
	
	/**
	 * Combine matrix columns and create an array using the values of $key1 as keys and $keys2 as values.
	 * 
	 * @param mixed[][] $matrix
	 * @param string $key1
	 * @param string $key2
	 * @return mixed[]
	 * @throws InvalidArgumentException if any key is invalid or $haystack is not a matrix
	 */
	static function combineColumns(array $matrix, $key1, $key2)
	{
		$data = array();
		foreach ($matrix as $row) {
			if (!is_array($row)) {
				throw new InvalidArgumentException("'$row' is not an array.");
			}
			if (!array_key_exists($key1, $row)) {
				throw new InvalidArgumentException("Key '$key1' does not exist in haystack.");
			}
			if (!array_key_exists($key2, $row)) {
				throw new InvalidArgumentException("Key '$key2' does not exist in haystack.");
			}
			$data["$key1"] = $row[$key2];
		}
		return $data;
	}
}

?>
