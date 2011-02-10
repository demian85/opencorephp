<?php

import('net.URL');

/**
 * Alias of URL::translate()
 *
 * @param string $route
 * @param string $language
 * @return string
 * @see URL#translate
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
function url($route, $language = null) {
	return URL::translate($route, $language);
}
?>