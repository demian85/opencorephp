<?php

//namespace util;

/**
 * Class with useful methods for manipulating strings
 * 
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 * @package util
 */
class Strings {

	private function __construct() { }

	/**
	 * Make a string suitable for URL's. Replace spaces by dashes and remove any non-alphanumeric characters.
	 *
	 * @param string $input
	 * @return string
	 */
	static function getSlug($input) {
		$input = str_replace(array('á','é','í','ó','ú'), array('a','e','i','o','u'), mb_strtolower($input));
		$input = preg_replace(array('#\s+#', '#[^\w-]#', '#-+#'), array('-', '', '-'), $input);
		return $input;
	}
	
	/**
	 * Create HTML anchor tags replacing URL's
	 * 
	 * @param string $input
	 * @param string $target anchor target attribute
	 * @return string
	 */
	static function parseLinks($input, $target = '_blank') {
		return preg_replace('#(^|\s)((?:http|https|ftp)://.+?\.[a-z]{2,3}.*?)(\s|$)#i', '$1<a href="$2" target="' . $target . '">$2</a>$3', $input);
	}

	/**
	 * Chop string so that it has at least $length characters.
	 *
	 * @param string $input
	 * @param int $length
	 * @param string $suffix
	 * @return string
	 */
	static function chop($input, $length = 75, $suffix = '...') {
		if (mb_strlen($input) > $length) return substr($input, 0, $length - mb_strlen($suffix)) . $suffix;
		else return $input;
	}

	/**
	 * Chop string so that it has at least $length words.
	 *
	 * @param string $input
	 * @param int $length
	 * @param string $suffix
	 * @return string
	 */
	static function chopWords($input, $length = 50, $suffix = '...') {
		$words = str_word_count($input, 2);		
		if (count($words) > $length) {
			$words = array_slice($words, 0, $length, true);
			end($words);
			return substr($input, 0, key($words) + mb_strlen(current($words))) . $suffix;
		}
		else return $input;
	}
}

?>
