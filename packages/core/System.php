<?php

//namespace core;

/**
 * Core utility methods.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
final class System
{
	private function __construct() { }

	/**
	 * Default shutdown handler.
	 * Captures fatal errors and logs them.
	 *
	 * @return void
	 */
	static function handleShutdown() {
		$e = error_get_last();
		if ($e && $e['type'] == E_ERROR || $e['type'] == E_CORE_ERROR) {
			import('log.Logger');
			$str = "Fatal Error: {$e['message']} in file {$e['file']} on line {$e['line']}.";
			try {
				Logger::getInstance()->log($str, Logger::TYPE_ERROR);
			} catch (Exception $ex) { }
		}
	}

	/**
	 * Default error handler.
	 * Overrides default PHP error handler and logs all errors.
	 * If error has been suppressed by using @ this function will do nothing and the default php error handler is bypassed.
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return boolean
	 */
	static function handleError($errno, $errstr, $errfile, $errline) {
		if (error_reporting() === 0) return true;

		import('log.Logger');

		switch ($errno) {
			case E_WARNING:
			case E_NOTICE:
			case E_STRICT:
			case @E_DEPRECATED:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case @E_USER_DEPRECATED:
				$type = Logger::TYPE_WARNING;
				$exit = false;
				break;
			case E_RECOVERABLE_ERROR:
				$type = Logger::TYPE_ERROR;
				$exit = false;
				break;
			default:
				$type = Logger::TYPE_ERROR;
				$exit = true;

		}

		Logger::getInstance()->log("$errstr in file $errfile on line $errline.", $type);

		if ($exit) exit(1);

		return true;
	}

	/**
	 * Generic Exception handler. This always clears the output buffer (if active).
	 * If production mode is enabled, no exception information is shown.
	 * If request is via AJAX, an object with the property 'error' is sent via the header 'X-JSON'.
	 *
	 * @param Exception $ex
	 * @return void
	 */
	static function handleException(Exception $ex) {
		if (ob_get_contents() !== false) @ob_clean();

		if (!($ex instanceof LoggerException)) {
			import('log.Logger');
			try {
				Logger::getInstance()->log($ex, Logger::TYPE_EXCEPTION);
			} catch (LoggerException $ex2) {
				self::handleException($ex2);
			}
		}

		$req = Request::getInstance();

		$style =<<<STR
<style type="text/css">
a { color:#fff; }
body { font-size:14px; color:#ddd; background:#4F4F4F; }
h1 { font-size:20px; font-family:monospace; color:#CFC126; }
table { border:1px solid gray; border-collapse:collapse; }
th { text-align:right; }
th, td { font-size:14px; padding:3px 6px; vertical-align:top; border:1px solid gray; }
td, .pre { white-space:pre; font-family:monospace; }
#object { padding:6px; border:1px solid gray; overflow:auto; }
</style>
STR;
		if (IN_PRODUCTION) {

			if ($req->isAjax()) {
				$error = json_encode(array('error'	=> l('Ooops... A system exception occured and has been reported. Please try again later.')));
				header("X-JSON: $error");
				echo $error;
				exit;
			}

			if ($req->isWebRequest()) {
				$html = '<!DOCTYPE html><html><head>'.$style.'<title>'.l('System exception').'</title></head><body><div class="system-exception">'.l('Ooops... A system exception occured and has been reported. Please try again later.').'</div></body></html>';
			}
			else {
				$html = l('System exception') . ":\n" . l('Ooops... A system exception occured and has been reported. Please try again later.') . "\n";
			}
		}
		else {

			if ($req->isAjax()) {
				$error = json_encode(array('error'	=> sprintf(l('%s not captured.'), get_class($ex))));
				header("X-JSON: $error");
				echo $error;
				exit;
			}

			$class = get_class($ex);

			if ($req->isWebRequest()) {

				$html = '<!DOCTYPE html><html><head>'.$style.'<title>'.$class.' not captured</title></head><body>';
				$html .=<<<HTML
				<h1>$class not captured</h1>
				<table>
					<tr>
						<th>File:</th>
						<td>{$ex->getFile()}</td>
					</tr>
					<tr>
						<th>Line:</th>
						<td>{$ex->getLine()}</td>
					</tr>
					<tr>
						<th>Message:</th>
						<td>{$ex->getMessage()}</td>
					</tr>
					<tr>
						<th>Trace:</th>
						<td>{$ex->getTraceAsString()}</td>
					</tr>
				</table>
				<div style="margin-top:20px;"><a href="javascript:;" onclick="document.getElementById('object').style.display=''; this.parentNode.removeChild(this)">Object »</a></div>
				<div class="pre" id="object" style="display:none">{$ex}</div>
HTML;
				$html .= '</body></html>';
			}
			else {
				$html = "$class not captured:\nFile: {$ex->getFile()}\nLine: {$ex->getLine()}\nMessage: {$ex->getMessage()}\nTrace:\n{$ex->getTraceAsString()}\n";
			}
		}

		die($html);
	}
}
?>