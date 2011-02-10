<?php

//namespace core;

import('util.Properties', 'util.Client');

/**
 * This class manages the system global configuration. It loads values from the configuration directory only once.
 * Each file must return an array with key entries.
 * You can later access to those values using the same notation as the Properties class.
 * This uses the singleton pattern, so there is only one instance of this class.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Config extends Properties
{
	/**
	 * @var Config
	 */
	protected static $instance = null;
	/**
	 * Directory path which holds the configuration files.
	 * @var string
	 */
	public static $configDir = null;
	/**
	 * Loaded configuration domain specific directory.
	 * @var string
	 */
	protected $_loadedConfigDir = null;

	/**
	 * Determine the config directory.
	 * Searches inside the default config directory for the first folder that matches the current domain (totally or partially)
	 * If no folder matches the domain, the default directory is used.
	 * If any core file is missing inside a specific domain folder, the base directory is used as fallback.
	 *
	 * @return string
	 */
	protected function _loadConfig()
	{
		$base = self::$configDir ? rtrim(self::$configDir, '/\\') : APPLICATION_DIR . '/config';

		$this->load($base);
		$this->_loadedConfigDir = $base;

		$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
					: (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');

		if ($domain) {
			$d = new DirectoryIterator($base);
			foreach ($d as $f) {
				if (!$d->isDir() || strpos($f, '.') === 0) continue;
				if (preg_match('#' . preg_quote($f->getFilename(), '#') . '$#i', $domain)) {
					$_dir = $base . DIRECTORY_SEPARATOR . $f;
					$this->load($_dir);
					$this->_loadedConfigDir = $_dir;
					break;
				}
			}
		}
	}

	/**
	 * Constructor. Loads config from files and initializes core application settings.
	 * 
	 * @throws FileNotFoundException if provided configuration directory is invalid or inaccessible.
	 * @throws IOException if an error occurred while loading configuration files.
	 */
	protected function Config()
	{
		// using old style constructor because of php visibility bug?
		parent::__construct();

		$this->_loadConfig();
	}
	
	/**
	 * Returns an instance of this class.
	 * 
	 * @return Config
	 * @throws FileNotFoundException if provided configuration directory is invalid or inaccessible.
	 * @throws IOException if an error occurred while loading configuration files.
	 */
	public static function getInstance()
	{
		if (self::$instance == null) self::$instance = new self();
		return self::$instance;
	}
	
	/**
	 * Initialize core config.
	 * @return void
	 */
	public function init()
	{
		// set shutdown function
		$onShutdown = $this->get('core.shutdown_handler');
		if ($onShutdown && is_callable($onShutdown)) {
			register_shutdown_function($onShutdown);
		}

		// set exception handler
		$exceptionHandler = $this->get('core.exception_handler');
		if ($exceptionHandler && is_callable($exceptionHandler)) {
			set_exception_handler($exceptionHandler);
		}

		// set error handler
		$errorHandler = $this->get('core.error_handler');
		if ($errorHandler && is_callable($errorHandler)) {
			set_error_handler($errorHandler);
		}

		if (IN_PRODUCTION) {
			ini_set('display_errors', false);
		}

		// set default timezone
		$tz = $this->get('core.timezone') ? $this->get('core.timezone') : @date_default_timezone_get();
		date_default_timezone_set($tz);

		// set multibyte lib internal encoding
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding($this->get('core.encoding'));
		}

		// register autoloader
		if ($this->get('core.autoload')) {
			spl_autoload_register(array('Loader', 'loadClass'));
		}
		
		// set locale
		if (!Request::getInstance()->isWebRequest() 
				|| Request::getInstance()->isWebRequest() && !$this->get('routes.language_redirect')) {
			$this->setLocale();
		}
	}
	
	/**
	 * Set locale. If NULL it will be autodetected by calling method Client::getLocaleInfo()
	 * If {routes.language_redirect} is enabled, this method should be called after Router instantiation in order to detect requested language!
	 *
	 * The following configuration values will be created:
	 * (string)	app.locale : the detected valid system locale
	 * (string)	app.country : the country code
	 * (string)	app.language : the language code
	 * 
	 * @param string $locale The locale identifier.
	 * @param boolean $setSystemLocale Calls setlocale() for category LC_ALL and tries different combinations of $locale until success.
	 * @return boolean TRUE on success or FALSE is setlocale() failed
	 * @see Lang#parseLocale
	 */
	public function setLocale($locale = null, $setSystemLocale = true)
	{
		if (!$locale) {
			$conf = $this->get('core.locale');
			if (is_string($conf) && !empty($conf)) {
				$locale = $conf;
			}
			else if (is_callable($conf)) {
				$locale = call_user_func($conf);
			}
			else {
				// autodetect
				$localeInfo = Client::getLocaleInfo();
				$locale = $localeInfo['locale'];
			}
		}
		
		$parts = Lang::parseLocale($locale);
		
		$this->set('app.locale', $locale);
		$this->set('app.country', $parts['country']);
		$this->set('app.language', $parts['language']);

		if ($setSystemLocale) {
			$encoding = $this->get('core.encoding');
			$codeset1 = strtolower(preg_replace('#[-_]+#', '', $encoding));
			$codeset2 = strtolower($encoding);
			$codeset3 = strtoupper($encoding);
			$l1 = "$locale.$codeset1";
			$l2 = "$locale.$codeset2";
			$l3 = "$locale.$codeset3";

			if (!setlocale(LC_ALL, $l1, $l2, $l3, $locale)) {
				return false;
			}
		}

		return true;
	}

	public function getLoadedConfigDir()
	{
		return $this->_loadedConfigDir;
	}
}
?>