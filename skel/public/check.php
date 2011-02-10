<?php

/**
 * This script checks the server configuration and loaded modules required by the framework to work properly.
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */

	require_once '../application/bootstrap.php';

	$config = Config::getInstance();

	ob_start();
	import('db.DB','gui.HTML');

	/*if (IN_PRODUCTION) {
		die("You cannot view this file in production mode.");
	}*/

	if ($_SERVER['QUERY_STRING'] == 'phpinfo') {
		phpinfo();
		exit;
	}

	function _printFlag($required, $v) {
		if ($required) {
			return $v ? '<span class="ok">On</span>' : '<span class="error">Off</span>';
		}
		else {
			return $v ? '<span class="error">On</span>' : '<span class="ok">Off</span>';
		}
	}

	$errors = array();
	$warnings = array();

	// write permissions in logs dir
	if (!is_writable($config->get('logs.path'))) {
		$errors[] = "{logs.dir} is not writable.";
	}

	// check required extensions
	$phpExtensions = get_loaded_extensions();
	sort($phpExtensions);
	$_ext = array('json','gd','mbstring','geoip','mysqli','gettext','SPL','Reflection');
	foreach ($_ext as $ext) {
		if (!in_array($ext, $phpExtensions)) {
			$errors[] = "Extension \"$ext\" not found.";
		}
	}

	// check required loaded apache modules
	$_tmp = array('mod_rewrite');
	foreach ($_tmp as $item) {
		if (!in_array($item, apache_get_modules())) {
			$errors[] = "Module \"$item\" not loaded.";
		}
	}

	// check useful extensions
	$_ext = array('apc','imagick');
	foreach ($_ext as $ext) {
		if (!in_array($ext, get_loaded_extensions())) {
			$warnings[] = "Extension \"$ext\" not found.";
		}
	}
	// check useful loaded apache modules
	$_tmp = array('mod_deflate','mod_expires','mod_headers');
	foreach ($_tmp as $item) {
		if (!in_array($item, apache_get_modules())) {
			$warnings[] = "Apache module \"$item\" not loaded.";
		}
	}

	// check htaccess
	if (!file_exists('.htaccess')) {
		$errors[] = ".htaccess file not found.";
	}

	// robots.txt
	$_robots = file_exists('robots.txt') ?
				preg_replace("#^(disallow:.*?)$#im", '<span class="error">$1</span>',
					file_get_contents('robots.txt')) : '';

	// sitemap
	$_sitemap = file_exists('sitemap.xml');

	// check db connection
	try {
		$_db = DB::getConnection();

		// check mysql and php time
		//$phpTime = date('Y-m-d H:i:s', time());
		//$dbTime = $_db->query("SELECT NOW() AS d")->fetchObject()->d;

	} catch (SQLException $ex) {
		$errors[] = "Unable to establish default database connection.";
	}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="en-US" />
<title>Server configuration check</title>
<style type="text/css">
	#main {
		margin:0 auto;
		overflow:auto;
	}
	body, td, th {
		font-size:12px;
	}
	table {
		border-collapse:collapse;
		border:1px solid black;
		margin:0 auto;
	}
	h1 {margin:0 0 5px 0; font-size:14px;}
	pre {margin:0;}
	.error {color:red;font-weight:bold;}
	.ok {color:green;font-weight:bold;}
	.center {text-align:center;}
	.errors {color:red;}
	.warnings {color:#AF5A15;}
	.wrap {white-space:normal;}
	.tbl {
		margin-bottom:20px;
	}
	.tbl td, .tbl th {
		padding:1px 3px;
		border:1px solid gray;
		white-space:nowrap;
		vertical-align:top;
	}
	.tbl ul {
		list-style-type:none;
		margin:0;
		padding:0;
	}
	.tbl ul li {
		width:150px;
		float:left;
	}
	#conf td {
		white-space:normal;
		font-size:11px;
		vertical-align:top;
		padding:0 3px;
		border:1px solid gray;
	}
	#conf tr td:first-child {
		text-align:right;
	}
	#left-col {
		overflow:auto;
		margin-right:100px;
	}
	#right-col {
		float:right;
	}
</style>
</head>
<body>
	<div id="main">
		<div id="right-col">
			<h1 class="center">Config values</h1>
			<?
				$configValues = array();
				foreach ($config->toArray() as $key => $value) {
					$_v = var_export($value, true);
					$configValues[] = array($key, "<pre>$_v</pre>");
				}
				echo HTML::table(array(), $configValues, 'conf');
			?>
		</div>

		<div id="left-col">
			<table class="tbl">
				<tr>
					<th>Config</th>
					<th>Required</th>
					<th>Value</th>
				</tr>
				<tr>
					<td>PHP Version</td>
					<td class="center">5.2</td>
					<td class="center"><?=PHP_VERSION?></td>
				</tr>
				<tr>
					<td>magic_quotes_gpc</td>
					<td class="center">Off</td>
					<td class="center"><?=_printFlag(0, ini_get('magic_quotes_gpc'))?></td>
				</tr>
				<tr>
					<td>register_globals</td>
					<td class="center">Off</td>
					<td class="center"><?=_printFlag(0, ini_get('register_globals'))?></td>
				</tr>
				<tr>
					<td>safe_mode</td>
					<td class="center">Off</td>
					<td class="center"><?=_printFlag(0, ini_get('safe_mode'))?></td>
				</tr>
			</table>

			<? if (!empty($errors)) { ?>
			<table class="tbl errors">
				<tr>
					<th>Errors</th>
				</tr>
				<?
					foreach ($errors as $e) {
				?>
				<tr>
					<td><?=htmlspecialchars($e)?></td>
				</tr>
				<? } ?>
			</table>
			<? } ?>

			<? if (!empty($warnings)) { ?>
			<table class="tbl warnings">
				<tr>
					<th>Warnings</th>
				</tr>
				<?
					foreach ($warnings as $w) {
				?>
				<tr>
					<td><?=htmlspecialchars($w)?></td>
				</tr>
				<? } ?>
			</table>
			<? } ?>

			<table class="tbl">
				<tr>
					<th colspan="2">Information</th>
				</tr>
				<tr>
					<th>Production Mode</th>
					<td><?=(IN_PRODUCTION ? 'Yes' : 'No')?></td>
				</tr>
				<tr>
					<th>Debug Mode</th>
					<td><?=(DEBUG_MODE ? 'Yes' : 'No')?></td>
				</tr>
				<tr>
					<th>Config directory</th>
					<td><?=$config->getLoadedConfigDir()?></td>
				</tr>
				<tr>
					<th>Error reporting</th>
					<td><?=error_reporting()?></td>
				</tr>
				<tr>
					<th>robots.txt</th>
					<td><?=nl2br($_robots)?></td>
				</tr>
				<tr>
					<th>sitemap.xml</th>
					<td><?=($_sitemap ? 'Found' : 'Not found')?></td>
				</tr>
				<tr>
					<th>Loaded Apache Modules</th>
					<td style="white-space: normal"><?=HTML::ulist(apache_get_modules())?></td>
				</tr>
				<tr>
					<th>Loaded PHP Extensions</th>
					<td style="white-space: normal"><?=HTML::ulist($phpExtensions)?></td>
				</tr>
			</table>

		</div>

		<div style="text-align:center; margin-top:20px; clear:both"><a href="?phpinfo">PHP Info</a></div>
	</div>
</body>
</html>
