<?php

//namespace util

/**
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Response {
	
	private function __construct() { }

	/**
	 * JSON response.
	 *
	 * @param mixed $data
	 * @param string $contentType
	 */
	static function json($data = null, $contentType = 'application/json') {
		header("Content-Type: $contentType");
		echo json_encode($data);
	}
	/**
	 * JSON with padding response.
	 *
	 * @param string $callback
	 * @param mixed $data
	 * @param string $contentType
	 */
	static function jsonp($callback, $data = null, $contentType = 'text/javascript') {
		header("Content-Type: $contentType");
		if ($callback) echo $callback . '(' . json_encode($data) . ')';
		else echo json_encode($data);
	}
	/**
	 * Send redirect header.
	 *
	 * @param string $location
	 * @param int $httpCode
	 */
	static function redirect($location, $httpCode = 302) {
		header("Location: $location", true, $httpCode);
		exit;
	}

	/**
	 * Force a file download.
	 *
	 * @param string $filePath
	 * @param string $contentType
	 * @param string $fileName
	 * @return void
	 * @throws FileNotFoundException if file does not exist
	 */
	static function fileDownload($filePath, $contentType = null, $fileName = null) {
		if (!file_exists($filePath)) {
			import('io.FileNotFoundException');
			throw new FileNotFoundException("File '$filePath' does not exist.");
		}
		if (!$contentType) {
			import('io.FileManager');
			$contentType = FileManager::getFileMime($filePath);
		}
		if (!$fileName) {
			$fileName = basename($filePath);
		}
		header("Content-type: $contentType");
		header('Content-Disposition: attachment; filename="'.$fileName.'"');
		readfile($filePath);
		exit;
	}
}

?>
