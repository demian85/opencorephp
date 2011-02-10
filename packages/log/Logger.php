<?php

//namespace util;

import('log.LoggerException');

/**
 * Class for application debug and message logging.
 * It provides a simple method for logging into a file, database or email.
 * If DEBUG_MODE is enabled, the log() method will try to log into FirePHP console.
 * DB table structure:
 * <code>
 	CREATE TABLE `Logs` (
          `log_id` int(11) NOT NULL auto_increment,
          `log_type` varchar(30) default NULL,
		  `log_date` datetime NOT NULL,
          `log_ip` varchar(20) NOT NULL default '0.0.0.0',
          `log_agent` varchar(255) default NULL,
          `log_lang` varchar(30) default NULL,
          `log_url` varchar(255) default NULL,
          `log_text` text,
          `log_request` text,
          `log_backtrace` text,
          `log_session` text,
          PRIMARY KEY  (`log_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8
	</code>
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Logger
{
	/**
	 * @var Logger
	 * @static
	 */
	protected static $instance;
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * Indicates log type
	 * @var int
	 */
	protected $logType;
	/**
	 * Indicates log location
	 * @var int
	 */
	protected $logLocation;
	/**
	 * Email addresses that will receive the log message
	 * @var string
	 */
	protected $logEmails;
	/**
	 * Prepared statement for database logging.
	 * @var Statement
	 */
	protected $stmt;
	/**
	 * Path to file or directory.
	 * @var string
	 */
	protected $logFilePath = null;
	/**
	 * Name of the database table.
	 * @var string
	 */
	protected $logTable;
	/**
	 * Indicates file logging.
	 * @var int
	 */
	const LOG_FILE = 1;
	/**
	 * Indicates database logging.
	 * @var int
	 */
	const LOG_DB = 2;
	/**
	 * Indicates email logging.
	 * @var int
	 */
	const LOG_EMAIL = 4;
	/**
	 * Indicates FirePHP logging.
	 * Requires Firefox w/Firebug & FirePHP extension and DEBUG_MODE enabled.
	 * @var int
	 */
	const LOG_FIREPHP = 8;
	/**
	 * Indicates standard output logging.
	 */
	const LOG_STDOUT = 16;
	/**
	 * Indicates ChromePHP logging. See http://www.chromephp.com/
	 * Requires Chrome w/ChromePhp extension and DEBUG_MODE enabled.
	 * @var int
	 */
	const LOG_CHROMEPHP = 32;

	/**
	 * Indicates a simple plain text log.
	 * @var int
	 */
	const TYPE_TEXT = 1;
	/**
	 * Indicates an exception.
	 * @var int
	 */
	const TYPE_EXCEPTION = 2;
	/**
	 * Indicates a warning.
	 * @var int
	 */
	const TYPE_WARNING = 4;
	/**
	 * Indicates an error.
	 * @var int
	 */
	const TYPE_ERROR = 8;
	/**
	 * Indicates an information.
	 * @var int
	 */
	const TYPE_INFO = 16;

	/**
	 * Constructor.
	 *
	 * @throws LoggerException if unable to initialize.
	 */
	protected function __construct()
	{
		try {
			$this->config = Config::getInstance();
			$this->setLogTable($this->config->get('logs.db_table'));
			$this->setLogLocation($this->config->get('logs.location'));
			$this->setLogEmails($this->config->get('logs.emails'));
			$this->setLogPath($this->config->get('logs.path'));
		} catch (Exception $ex) {
			throw new LoggerException("Unable to initialize Logger.", $ex);
		}
	}

	/**
	 * Initialize database connection and prepare statement.
	 *
	 * @return void
	 * @throws SQLException
	 */
	protected function initConnection()
	{
		import('db.DB');
		$conn = $this->config->get('logs.db_connection');
		$db = DB::getConnection($conn);
		$sql = "INSERT INTO {$this->logTable} (
					log_type, log_date, log_ip, log_agent,
					log_lang, log_url, log_text,
					log_request, log_backtrace, log_session
				) VALUES (
					?, ?, ?,
					?, ?, ?, ?,
					?, ?, ?
				)";
		$this->stmt = $db->prepare($sql);
	}

	/**
	 * Get log type name.
	 *
	 * @param int $type
	 * @return string
	 */
	protected function _getLogTypeName($type)
	{
		switch ($type) {
			default:
			case self::TYPE_TEXT:
				$v = "Text";
				break;
			case self::TYPE_EXCEPTION:
				$v = "Exception";
				break;
			case self::TYPE_ERROR:
				$v = "Error";
				break;
			case self::TYPE_WARNING:
				$v = "Warning";
				break;
			case self::TYPE_INFO:
				$v = "Info";
				break;
		}
		return $v;
	}

	protected function _getLogText($type, $input) {

		if (isset($_SESSION) && !empty($_SESSION)) {
			ob_start();
			@print_r($_SESSION);
			$session = ob_get_contents();
			ob_end_clean();
		}
		else $session = '';

		if (isset($_REQUEST) && !empty($_REQUEST)) {
			ob_start();
			@print_r($_REQUEST);
			$request = ob_get_contents();
			ob_end_clean();
		}
		else $request = '';

		if ($type == self::TYPE_TEXT) {
			$str = $input . "\n";
		}
		else {
			$_date = date("Y-m-d H:i:s");
			$str = "Type: {$this->_getLogTypeName($type)}\nDate: $_date\n";
			
			if (Request::getInstance()->isWebRequest()) {
				$userInfo = array(
					'ip'			=> @$_SERVER['REMOTE_ADDR'],
					'user_agent'	=> @$_SERVER['HTTP_USER_AGENT'],
					'language'		=> @$_SERVER['HTTP_ACCEPT_LANGUAGE'],
					'uri'			=> @$_SERVER['REQUEST_URI']
				);
				$str .= "IP: {$userInfo['ip']}\nHTTP_USER_AGENT: {$userInfo['user_agent']}\nHTTP_ACCEPT_LANGUAGE: {$userInfo['language']}\nREQUEST_URI: {$userInfo['uri']}\n";
			}

			switch ($type) {
				case self::TYPE_EXCEPTION:
					$str .= "\n$input\n\n";
					if ($session) $str .= "Session:\n$session\n";
					if ($request) $str .= "Request:\n$request\n";
					break;
				default:
					ob_start();
					debug_print_backtrace();
					$backtrace = ob_get_contents();
					ob_end_clean();
					$msg = trim($input);
					$str .= "\nMessage:\n$msg\n\n";
					if ($session) $str .= "Session:\n$session\n";
					if ($request) $str .= "Request:\n$request\n";
					$str .= "Backtrace:\n$backtrace\n";
			}
		}

		return $str;
	}

	protected function _getTargetLogFile()
	{
		if (is_dir($this->logFilePath)) {
			$filePath = $this->logFilePath . DIRECTORY_SEPARATOR . date($this->config['logs.file_name_format']);
			$fsize = (int)$this->config['logs.max_file_size'];
			if ($fsize > 0 && file_exists($filePath) && filesize($filePath) >= $fsize) {
				$fileInfo = pathinfo($filePath);
				$count = 1;
				do {					
					$filePath = $this->logFilePath . DIRECTORY_SEPARATOR . $fileInfo['filename'] . '.' . $count . ($fileInfo['extension'] ? '.' . $fileInfo['extension'] : '');
					$count++;
				} while (file_exists($filePath) && filesize($filePath) >= $fsize);
			}
			return $filePath;
		}
		else {
			return $this->logFilePath;
		}
	}

	/**
	 * Return instance of this class.
	 *
	 * @return Logger
	 * @throws LoggerException if unable to initialize.
	 */
	public static function getInstance()
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Set default log type.
	 *
	 * @param int $type
	 * @return void
	 */
	public function setLogType($type)
	{
		$this->logType = (int)$type;
	}

	/**
	 * Sets log location. Use any combination of this class' constants LOG_*
	 *
	 * @param int $location
	 * @return void
	 */
	public function setLogLocation($location)
	{
		$this->logLocation = (int)$location;
	}

	/**
	 * Set log path. It must be a valid writable directory or file.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setLogPath($path)
	{
		$path = rtrim($path, '/\\');
		$this->logFilePath = $path;
	}

	/**
	 * Set database log table name.
	 *
	 * @param string $table
	 * @return void
	 */
	public function setLogTable($table)
	{
		$this->logTable = (string)$table;
	}

	/**
	 * If email logging is enabled, indicates the emails where the log will be sent.
	 * Separate multiple email addresses by commas.
	 *
	 * @param string $emails
	 * @return void
	 */
	public function setLogEmails($emails)
	{
		$this->logEmails = (string)$emails;
	}

	/**
	 * Log info message
	 *
	 * @param mixed $input
	 * @param int $location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function info($input, $location = 0)
	{
		return $this->log($input, self::TYPE_INFO, $location);
	}

	/**
	 * Log error message
	 *
	 * @param mixed $input
	 * @param int $location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function error($input, $location = 0)
	{
		return $this->log($input, self::TYPE_ERROR, $location);
	}

	/**
	 * Log warning message
	 *
	 * @param mixed $input
	 * @param int $location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function warn($input, $location = 0)
	{
		return $this->log($input, self::TYPE_WARNING, $location);
	}

	/**
	 * Log exception
	 *
	 * @param Exception $ex
	 * @param int $location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function exception(Exception $ex, $location = 0)
	{
		return $this->log($ex, self::TYPE_EXCEPTION, $location);
	}

	/**
	 * Log a PHP variable. The output is the same sa var_dump()
	 *
	 * @param mixed $var
	 * @param string $label
	 * @param int $location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function dump($var, $label = '', $location = 0)
	{
		ob_start();
		@var_dump($var);
		$dump = ob_get_contents();
		ob_end_clean();
		if ($label) $dump = $label . ":\n" . $dump;
		return $this->log($dump, self::TYPE_TEXT, $location);
	}

	/**
	 * Log message into a file.
	 *
	 * @param mixed $message
	 * @param int $type
	 * @return void
	 * @throws LoggerException if an error occurs.
	 */
	public function logFile($message, $type = self::TYPE_TEXT)
	{
		$msg = $this->_getLogText($type, $message);
		$msg .= str_repeat("_", 50) . "\n";
		$target = $this->_getTargetLogFile();
		$f = @fopen($target, 'a+');
		if (!@fwrite($f, $msg)) {
			throw new LoggerException("Could not write to file '{$target}'.");
		}
		@fclose($f);
	}

	/**
	 * Log message. Returns FALSE if logging is disabled.
	 *
	 * @param mixed $message
	 * @param int $type
	 * @param int $location Override log location
	 * @return boolean
	 * @throws LoggerException if an error occurs.
	 */
	public function log($message, $type = self::TYPE_TEXT, $location = 0)
	{
		$logLocation = $location > 0 ? $location : $this->logLocation;

		if ($logLocation == 0) return false;

		if ($logLocation & self::LOG_FILE) {
			$this->logFile($message, $type);
		}

		if ($logLocation & self::LOG_EMAIL) {
			$_msg = $this->_getLogText($type, $message);
			$_fromEmail = 'noreply@' . $this->config->get('core.domain');
			$headers = "From: {$this->config->get('core.domain')} <$_fromEmail>\r\n";
			$headers .= "Reply-To: $_fromEmail\r\n";
			$headers .= "Content-type: text/plain;";
			$subject = $this->config->get('core.domain') . ': ' . $this->_getLogTypeName($type);

			if (!mail($this->logEmails, $subject, wordwrap($_msg, 70), $headers)) {
				throw new LoggerException("Could not send email to addresses: " . $this->logEmails . '.');
			}
		}

		if ($logLocation & self::LOG_DB) {
			ob_start();
			@print_r($_SESSION);
			$session = ob_get_contents();
			ob_end_clean();

			ob_start();
			@print_r($_REQUEST);
			$request = ob_get_contents();
			ob_end_clean();

			ob_start();
			debug_print_backtrace();
			$backtrace = ob_get_contents();
			ob_end_clean();

			try {
				if (!$this->stmt) $this->initConnection();
				$this->stmt->bindValue(1, $this->_getLogTypeName($type), Statement::PARAM_STR);
				$this->stmt->bindValue(2, date('Y-m-d H:i:s'), Statement::PARAM_STR);
				$this->stmt->bindValue(3, @$_SERVER['REMOTE_ADDR'], Statement::PARAM_STR);
				$this->stmt->bindValue(4, @$_SERVER['HTTP_USER_AGENT'], Statement::PARAM_STR);
				$this->stmt->bindValue(5, @$_SERVER['HTTP_ACCEPT_LANGUAGE'], Statement::PARAM_STR);
				$this->stmt->bindValue(6, @$_SERVER['REQUEST_URI'], Statement::PARAM_STR);
				$this->stmt->bindValue(7, $message, Statement::PARAM_STR);
				$this->stmt->bindValue(8, $request, Statement::PARAM_STR);
				$this->stmt->bindValue(9, $backtrace, Statement::PARAM_STR);
				$this->stmt->bindValue(10, $session, Statement::PARAM_STR);
				$this->stmt->execute();
			} catch (Exception $ex) {
				throw new LoggerException("Could not log into database", $ex);
			}
		}

		if (DEBUG_MODE && ($logLocation & self::LOG_FIREPHP) && Request::getInstance()->isWebRequest()) {
			import('log.FirePHP');
			try {
				$fphp = FirePHP::getInstance(true);
				switch ($type) {
					default:
					case self::TYPE_TEXT:
						$fphp->log($message);
						break;
					case self::TYPE_EXCEPTION:
					case self::TYPE_ERROR:
						$fphp->error($message);
						break;
					case self::TYPE_WARNING:
						$fphp->warn($message);
						break;
					case self::TYPE_INFO:
						$fphp->info($message);
						break;
				}
			} catch (Exception $ex) {
				throw new LoggerException("Could not log into FirePHP", $ex);
			}
		}

		if (DEBUG_MODE && ($logLocation & self::LOG_CHROMEPHP) && Request::getInstance()->isWebRequest()) {
			import('log.ChromePhp');
			try {
				switch ($type) {
					default:
					case self::TYPE_TEXT:
						case self::TYPE_INFO:
						ChromePhp::log($message);
						break;
					case self::TYPE_EXCEPTION:
					case self::TYPE_ERROR:
						ChromePhp::error($message);
						break;
					case self::TYPE_WARNING:
						ChromePhp::warn($message);
						break;
				}
			} catch (Exception $ex) {
				throw new LoggerException("Could not log into ChromePHP", $ex);
			}
		}

		if ($logLocation & self::LOG_STDOUT) {
			echo $this->_getLogText($type, $message);
		}

		return true;
	}
}
?>
