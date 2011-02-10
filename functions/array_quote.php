<?php

/**
 * Quotes array items recursively.
 *
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 * @param mixed $input
 * @return string
 */
function array_quote($input) {
	return is_array($input) ? array_map('array_quote', $input) : "'$input'";
}
?>