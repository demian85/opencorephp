<?php

// namespace core;

/**
 * This class has useful static methods for loading classes and files.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Loader
{
	protected function __construct() { }

	/**
	 * Loads a class from a PHP file. The filename must be formatted as "$className.php".
	 * Each namespace will be considered as a folder.
	 * Eg: "db\mysql\Connection" will be tranlated to $dir/db/mysql/Connection.php
	 *
	 * @param string $className Class name which can include namespaces.
	 * @param string|string[] $dirs A single directory or an array of directories to search for the class. If null, {core.class_path} will be used.
	 * @return void
	 * @throws FileNotFoundException if $dir is not a valid directory.
	 * @throws ClassNotFoundException if unable to load class.
	 * @throws ClassDefNotFoundException if the class definition could not be found.
	 */
	public static function loadClass($className, $dirs = null)
	{
		if (!$dirs) {
			$dirs = Config::getInstance()->get('core.class_path');
		}
		$baseName = str_replace("\\", DIRECTORY_SEPARATOR, $className) . '.php';
		foreach ((array)$dirs as $dir) {
			if (!is_dir($dir) || !is_readable($dir)) {
				import('io.FileNotFoundException');
				throw new FileNotFoundException("Directory '$dir' could not be found or is inaccessible.");
			}
			$file = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $baseName;
			if (file_exists($file)) {
				require_once($file);
				// if PHP_VERSION < 5.3 we don't have namespaces, so fetch only the class name...
				if (!defined("__NAMESPACE__") && ($pos = strrpos($className, '\\')) !== false) {
					$className = substr($className, $pos+1);
				}
				if (!class_exists($className, false) && !interface_exists($className, false)) {
					throw new ClassDefNotFoundException("File '$file' was loaded but class '$className' could not be found.");
				}
				return;
			}
		}
		throw new ClassNotFoundException("Unable to load class '$className'. File '$baseName' could not be found.");
	}

	/**
	 * Includes all files in $dir
	 *
	 * @param string $dir
	 * @param string|array $extensions Valid lowercase extension(s) to look for.
	 * @param boolean $recursive
	 * @throws FileNotFoundException if $dir could not be found or is inaccessible.
	 */
	public static function includeFiles($dir, $extensions = 'php', $recursive = false)
	{
		if (!is_dir($dir) || !is_readable($dir)) {
			import('io.FileNotFoundException');
			throw new FileNotFoundException("Directory '$dir' could not be found or is inaccessible.");
		}
		$d = opendir($dir);
		while (($file = readdir($d)) !== false) {
			if (strpos($file, '.') !== 0) {
				$filePath = $dir . DIRECTORY_SEPARATOR . $file;
				if (!is_dir($filePath)) {
					$ext = strtolower(substr($filePath, strrpos($filePath, '.')+1));
					if (in_array($ext, (array)$extensions)) {
						require_once($filePath);
					}
				}
				else if ($recursive) {
					self::includeFiles($filePath, $extensions, $recursive);
				}
			}
		}
		closedir($d);
	}
}
?>