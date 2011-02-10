<?php

/**
 * Revert magic_quotes_gpc (if active) by stripping slashes from supeglobal vars _GET, _POST and _COOKIE
 *
 * @return void
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
function revertMagicQuotesGPC() {
	if (ini_get('magic_quotes_gpc') == 1) {
		function _revertMagicQuotesGPC(&$value, $key) {
			$value = stripslashes($value);
		}
		// TODO Lambda function
		array_walk_recursive($_GET, '_revertMagicQuotesGPC');
		array_walk_recursive($_POST, '_revertMagicQuotesGPC');
		array_walk_recursive($_COOKIE, '_revertMagicQuotesGPC');
	}
}
?>