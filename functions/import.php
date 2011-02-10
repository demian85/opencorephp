<?php

/**
 * This function imports packages or a single class/interface.
 *
 * @param string Package or class to import. Packages are separated by dots and is represented by a directory.
 * @return void
 * @throws RuntimeException if package or class could not be found.
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
function import()
{
	$packages = func_get_args();
	$packageDir = FRAMEWORK_DIR . '/';
	foreach ($packages as $package) {
		$path = "packages" . DIRECTORY_SEPARATOR . str_replace(".", DIRECTORY_SEPARATOR, $package);
		if (substr($path, -1) == "*") {
			$dirPath = $packageDir . substr($path, 0, -2);
			if (!is_dir($dirPath)) {
				throw new RuntimeException("Could not load package '$package'. Directory '$dirPath' not found.");
			}
			$d = opendir($dirPath);
			while (($file = readdir($d)) !== false) {
				if (strpos($file, '.') !== 0) {
					$filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
					if (!is_dir($filePath)) {
						$ext = substr($filePath, strrpos($filePath, '.')+1);
						if ($ext == 'php') {
							require_once($filePath);
						}
					}
				}
			}
			closedir($d);
		}
		else {
			$file = $packageDir . "$path.php";
			if (!file_exists($file)) {
				throw new RuntimeException("Could not load class '".basename($path)."'. File '$file' not found.");
			}
			require_once($file);
		}
	}
}
?>