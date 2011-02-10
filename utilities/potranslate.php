<?php

/**
 * This script parses a .po file and extracts strings
 * Usage:
 * 
 * php potranslate.php source.po destination.txt
 * 
 * Options (last parameter):
 * --no-trans : export only original strings
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
	if ($_SERVER['argc'] < 3) {
		die("You must provide an input .po file path as the first argument and the output catalog file path as the second\n");
	}
	
	$input = $_SERVER['argv'][1];
	$output = $_SERVER['argv'][2];
	$noTranslate = isset($_SERVER['argv'][3]) && $_SERVER['argv'][3] == '--no-trans';
	
	$str = file_get_contents($input);
	$matches = null;
	
	function fixQuotes($str) {
		return preg_replace('#\\\\"#', '"', $str);
	}
	
	if ($noTranslate) {
		$regex = '#^(?:msgid)\ " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ixm';
	}
	else {
		$regex = '#^(?:msgid|msgstr)\ " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ixm';
	}
	
	preg_match_all($regex, $str, $matches, PREG_SET_ORDER);

	$out = '';
	
	if ($noTranslate) {
		$length = count($matches);
		for ($i = 0; $i < $length; $i++) {
			if (!empty($matches[$i][1])) {
				$out .= fixQuotes($matches[$i][1]) . "\n";
			}
		}
	}
	else {
		$length = count($matches)-1;
		for ($i = 0; $i < $length; $i += 2) {
			if (!empty($matches[$i][1])) {
				$out .= fixQuotes($matches[$i][1]) . "\n" . fixQuotes($matches[$i+1][1]) . "\n\n";
			}
		}
	}
		
	
	file_put_contents($output, $out);

	echo "\nSuccessfully converted " . count($matches) . " strings\n";
?>
