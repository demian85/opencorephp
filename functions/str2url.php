<?php

/**
 * Make a string suitable for URL's. Replace spaces by dashes and remove any non-alphanumeric characters.
 * Use Strings#getSlug instead
 *
 * @param string $input
 * @return string
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 * @deprecated
 * @see Strings#getSlug
 */
function str2url($input) {
	$input = str_replace(array('á','é','í','ó','ú'), array('a','e','i','o','u'), mb_strtolower($input));
	$input = preg_replace(array('#\s+#', '#[^\w-]#', '#-+#'), array('-', '', '-'), $input);
	return $input;
}
?>