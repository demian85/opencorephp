<?php

// namespace io;

/**
 * This class has useful static methods for file management.
 *
 * @package io
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class FileManager
{
	/**
	 * Move uploaded file to a specified location.
	 *
	 * @param string $tmpName
	 * @param string $originalName
	 * @param string $targetDir
	 * @param string $name
	 * @param boolean $clean
	 * @param int $perms
	 * @return string
	 */
	private static function _uploadFile($tmpName, $originalName, $targetDir, $name, $clean, $perms = 0)
	{
		if (is_uploaded_file($tmpName)) {
			if (!is_dir($targetDir)) {
				import('io.IOException');
				throw new IOException("'$targetDir' is not a valid directory.");
			}

			if (!$name) {
				$_info = pathinfo($originalName);
				if (isset($_info['extension'])) $originalName = $_info['filename'];
				$_ext = isset($_info['extension']) ? '.' . $_info['extension'] : '';
				$name = $clean ? preg_replace("#[^\\w]#", "_", $originalName) : $originalName;
				$name .= $_ext;
			}

			$targetPath = self::getUniqueFileName($targetDir . DIRECTORY_SEPARATOR . $name);

			if (!move_uploaded_file($tmpName, $targetPath)) {
				import('io.IOException');
				throw new IOException("Unable to move uploaded file. Check write permissions for directory '$targetDir'");
			}

			if ($perms) {
				@chmod($targetPath, $perms);
			}

			return $targetPath;
		}

		return null;
	}

	public function __construct() {}

	/**
	 * Check for uploaded file and move it to the specified location.
	 * If the file name is an array, the method will return an array of file paths.
	 * If a file with the same name exists in the specified location, it will be renamed by adding a suffix.
	 *
	 * @param string $fileName Name of the file variable received via POST.
	 * @param string $targetDir Target directory path
	 * @param string $name A name for the uploaded file, if NULL, the same will be used.
	 * @param boolean $clean Clean the file name. Removes non-alphanumeric characters and reeplaces them with a "_". This option is only valid if $name is null.
	 * @param int $perms Octal permissions for the uploaded file(s)
	 * @return string|string[] The new file(s) path(s) or NULL if the file is not a valid uploaded file.
	 * @throws IOException if $targetDir is invalid or an error occured while moving file.
	 * @throws FileUploadException if an error occured while uploading file.
	 */
	public static function upload($fileName, $targetDir, $name = null, $clean = true, $perms = 0765)
	{
		if (!isset($_FILES[$fileName]) || empty($_FILES[$fileName]['name'])) return null;

		if (is_array($_FILES[$fileName]['tmp_name'])) {
			$files = array();
			foreach ($_FILES[$fileName]['name'] as $k => $v) {
				if (empty($v)) continue;
				if ($_FILES[$fileName]['error'][$k] > 0) {
					import('io.FileUploadException');
					throw new FileUploadException($_FILES[$fileName]['error'][$k]);
				}
				$_f = self::_uploadFile($_FILES[$fileName]['tmp_name'][$k], $v, $targetDir, $name, $clean, $perms);
				if ($_f) $files[] = $_f;
			}
			return $files;
		}
		else {
			if ($_FILES[$fileName]['error'] > 0) {
				import('io.FileUploadException');
				throw new FileUploadException($_FILES[$fileName]['error']);
			}
			return self::_uploadFile($_FILES[$fileName]['tmp_name'],
										$_FILES[$fileName]['name'], $targetDir, $name, $clean, $perms);
		}
	}
	/**
	 * Get file extension.
	 *
	 * @param string $file File name or absolute path
	 * @return string
	 */
	public static function getExtension($file)
	{
		$info = pathinfo($file);
		return isset($info['extension']) ? $info['extension'] : null;
	}

	/**
	 * Format file size.
	 *
	 * @param integer $size File size in bytes
	 * @return string Formatted file size
	 */
	public static function formatSize($size)
	{
		$measures = array (
			0 => array("Bytes", 0),
			1 => array("KB", 0),
			2 => array("MB", 1),
			3 => array("GB", 2),
			4 => array("TB", 2)
		);
		$file_size = $size;
		for ($i = 0; $file_size >= 1024; $i++) {
			$file_size = $file_size / 1024;
		}
		$file_size = number_format($file_size, $measures[$i][1]);
		return $file_size." ".$measures[$i][0];
	}

	/**
	 * Get file mime type using the 'file' Linux command
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getFileMime($path)
	{
		return trim(exec('file -bi '.escapeshellarg($path)));
	}

	/**
	 * Return path of the specified file with a unique name for that directory.
	 *
	 * @param string $path File path
	 * @param boolean $cleanName Clean file name, replacing non-alphanumeric characters with an underscore.
	 * @return string New file path
	 */
	public static function getUniqueFileName($path, $cleanName = false)
	{
		$counter = 2;
		$add = '';
		$fileInfo = pathinfo($path);
		$fileName = $cleanName ? preg_replace("#[^\\w]#i", "_", $fileInfo['filename']) : $fileInfo['filename'];
		$extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
		while (file_exists($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileName . $add . $extension))	{
			$add = '_' . $counter++;
		}
		return $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fileName . $add . $extension;
	}

	/**
	 * Check if a file exists in $dir using a regular expression.
	 *
	 * @param string $dir Directory in which the file should be located.
	 * @param string $regex Regular expression that should match the file name including the extension.
	 * @return boolean
	 */
	public static function fileExists($dir, $regex)
	{
		$i = new DirectoryIterator($dir);
		foreach ($i as $file) {
			if (preg_match($regex, $file->getFileName())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Rename files using a regular expression.
	 *
	 * @param string $dir Directory path where filess will be searched.
	 * @param string $find Regex that will test the complete file name.
	 * @param string $replace The new name for the file. Captured groups are available using the same sintax described in php function "preg_replace"
	 * @param boolean $recursive Rename recursively
	 * @return integer The number of renamed files.
	 * @throws IOException If any I/O error occured.
	 */
	public function rename($dir, $find, $replace, $recursive = false)
	{
		$count = 0;
		$i = new DirectoryIterator($dir);
		foreach ($i as $file) {
			if ($file->isDot()) continue;
			$name = $file->getFileName();
			if ($file->isDir() && $recursive) {
				$count += $this->rename($file->getPathName(), $find, $replace, $recursive);
			}
			if (@preg_match($find, $name)) {
				$newName = @preg_replace($find, $replace, $name);
				$dir = dirname($file->getPathName());
				if (!@rename($file->getPathName(), $dir.'/'.$newName)) {
					import('io.IOException');
					throw new IOException("Couldn't rename file ".$file->getPathName());
				}
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Mover files using a regular expression.
	 *
	 * @param string $sourceDir Directory path where files will be searched.
	 * @param string $destinationDir Directory path where files will be moved.
	 * @param string $regex Regex
	 * @param boolean $recursive Rename recursively
	 * @return integer The number of moved files.
	 * @throws IOException If any I/O error occured.
	 */
	public function move($sourceDir, $destinationDir, $regex, $recursive = false)
	{
		$count = 0;
		$destinationDir = preg_replace("#[/\\\\]$#", "", $destinationDir);
		$i = new DirectoryIterator($sourceDir);
		foreach ($i as $file) {
			if ($file->isDot()) continue;
			$name = $file->getFileName();
			if ($file->isDir() && $recursive) $count += $this->move($file->getPathName(), $destinationDir, $regex, $recursive);
			if (@preg_match($regex, $name)) {
				if (!@rename($file->getPathName(), $destinationDir.APP_PATH_SEPARATOR.$name)) {
					import('io.IOException');
					throw new IOException("Couldn't move file ".$file->getPathName());
				}
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Delete files using a regular expression.
	 *
	 * @param string $dir Directory path where files will be searched.
	 * @param string $regex Regex
	 * @param boolean $recursive Delete recursively
	 * @return integer The number of deleted files.
	 * @throws IOException If any I/O error occured.
	 */
	public function delete($dir, $regex, $recursive = false)
	{
		$count = 0;
		$i = new DirectoryIterator($dir);
		foreach ($i as $file) {
			if ($file->isDot()) continue;
			$name = $file->getFileName();
			if ($file->isDir() && $recursive) $count += $this->delete($file->getPathName(), $regex, $recursive);
			if (@preg_match($regex, $name)) {
				if (!@unlink($file->getPathName())) {
					import('io.IOException');
					throw new IOException("Couldn't delete file ".$file->getPathName());
				}
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Return an array with information of files under the specified directory.
	 * Optionally, searches for files recursively.
	 *
	 * @param 	string	$dir Directory path.
	 * @param 	boolean $includeDirs Include directories.
	 * @param 	boolean	$recursive Descend into sub-directories.
	 * @param 	boolean	$hiddenFiles Include hidden files.
	 * @param 	string 	$regex Only return files that matches this regular expression.
	 * @param 	string	$sortBy Sort by this column name. See below the possible values.
	 * @param 	integer $sortMode Sort mode. Possible values are SORT_ASC, SORT_DESC.
	 * @return 	mixed[][] A matrix where each entry is an array with the following properties
		  					name 	=> complete file name
		  					path 	=> complete file path
		  					size	=> file size in bytes
		  					fsize	=> formatted file size
		  					ext		=> file extension
		  					mtime	=> file modification time
		  					mime	=> file mime type
		  					isdir	=> a boolean indicating if file is a directory
		  					isimage	=> a boolean indicating if file is an image
	 */
	public function listFiles($dir, $includeDirs = false, $recursive = true, $hiddenFiles = false,
								$regex = '', $sortBy = '', $sortMode = SORT_ASC)
	{
		$dir = preg_replace("#[/\\\\]$#", "", $dir);
		$i = new DirectoryIterator($dir);
		$files = array();

		foreach ($i as $file) {
			if ($file->isDot()) continue;
			if (!$hiddenFiles && strpos($file->getFileName(), ".") === 0) continue;
			if ($recursive && $file->isDir()) {
				$files = array_merge($files,
							$this->listFiles($dir.DIRECTORY_SEPARATOR.$file->getFileName(),
												$includeDirs, $recursive, $hiddenFiles, $regex));
			}
			if (!$file->isDir() || $file->isDir() && $includeDirs) {
				$pathName = $file->getPathName();
				$name = $file->getFileName();
				if ($regex && !@preg_match($regex, $name)) continue;
				$extension = $file->isDir() ? '' : strtolower(substr($file->getFileName(), strrpos($file->getFileName(), ".")+1));
				$files[] = array(
					'name' 		=> $name,
					'dirpath'	=> $dir,
					'path' 		=> $pathName,
					'size' 		=> $file->getSize(),
					'fsize' 	=> self::formatSize($file->getSize()),
					'ext'		=> $extension,
					'mtime'		=> $file->getMTime(),
					'mime'		=> self::getFileMime($pathName),
					'isdir'		=> $file->isDir(),
					'isimage'	=> in_array($extension, array("jpg","jpeg","gif","png","bmp","tif","tiff"))
				);
			}
		}
		if (!empty($files) && $sortBy)
		{
			foreach ($files as $f) $cols[] = $f[$sortBy];
			array_multisort($cols, $sortMode, $files);
		}
		return $files;
	}
}
?>
