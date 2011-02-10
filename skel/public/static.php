<?php

	// Main core initialization
	require_once(dirname(__FILE__) .  '/../application/bootstrap.php');

	define('COMPRESS_CSS', 1);
	define('COMPRESS_JS', 2);

	// Init config
	$config = Config::getInstance();
	$config->init();
	
	$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
	$compress = isset($_GET['compress']) ? (int)$_GET['compress'] : '';
	$files = isset($_GET['files']) ? explode(';', $_GET['files']) : array();
	
	function loadSource($path, $type, $compress = false) {
		$config = Config::getInstance();
		$request = Request::getInstance();
		$path = preg_replace('#\.\.?/#', '', $path);
		$filePath = realpath($path);

		if (!$filePath) {
			if (strpos($path, '/') === 0) {
				$filePath = $config['core.root'] . $path;
				if (!file_exists($filePath)) {
					if (DEBUG_MODE) {
						import('log.Logger');
						$_error = ($type == 'js') ? 'JS file not found: ' . $path
									: ($type == 'css' ? 'CSS file not found: ' . $path : '');
						Logger::getInstance()->error($_error);
					}
					$source = '';
				}
				else {
					$source = file_get_contents($filePath);
				}
			}
			else if (strpos($path, 'http') === 0) {
				$source = file_get_contents($path);
			}
		}
		else {
			$source = file_get_contents($filePath);
		}

		if ($compress) {
			$source = str_replace(array("\n", "\t", "\r"), '', $source);
		}
		
		return (string)$source;
	}
	
	$source = '';
	
	switch ($type) {
		case 'js':
			foreach ($files as $f) {
				$source .= "/*========== $f =========*/\n" . loadSource($f, 'js', $compress & COMPRESS_JS) . "\n\n";
			}
			header("Content-Type: text/javascript;");
			break;
		case 'css':
			foreach ($files as $f) {
				$source .= "/*========== $f =========*/\n" . loadSource($f, 'css', $compress & COMPRESS_CSS) . "\n\n";
			}
			header("Content-Type: text/css;");
			break;
	}

	if ($config['views.static_loader.expires'] > 0) {
		$maxAge = $config['views.static_loader.expires'];
		header("Cache-Control: max-age=$maxAge, public, must-revalidate");
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + $maxAge) . " GMT");
	}
	
	echo $source;
?>
