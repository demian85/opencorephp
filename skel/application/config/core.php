<?php


/**
 * Core application config
 */
$config = array(
/**
 * Document root absolute path (without trailing slash)
 */
'root'		=> @$_SERVER['DOCUMENT_ROOT'],
/**
 * Domain - DO NOT include subdomains labels!!
 */
'domain' 	=> @$_SERVER['HTTP_HOST'],
/**
 * Application encoding. Should match file encoding.
 */
'encoding'	=> 'UTF-8',
/**
 * System locale. Can be a string or a callback that returns a valid locale.
 * If empty, locale will be automatically detected by calling method Client::getLocaleInfo().
 */
'locale'	=> '',
/**
 * Valid PHP timezone identifier. Eg: Etc/GMT-3, America/Argentina/Buenos_Aires
 * If empty, default system timezone will be used (if available)
 */
'timezone'	=> '',
/**
 * If enabled, Loader::loadClass() method will be registered as the class loader.
 * Uses {core.class_path} to search for the class.
 */
'autoload'	=> true,
/**
 * When a class needs to be loaded automatically, Loader class tries to find it inside this path.
 * You may supply an array of directories. PHP include path is NOT included by default.
 */
'class_path'	=> array(
					APPLICATION_DIR . '/classes',
					APPLICATION_DIR . '/models'
				),
/**
 * Callback for handling uncaptured exceptions.
 * Function/method must be defined and included before initializing the system config.
 */
'exception_handler'	=> array('System', 'handleException'),
/**
 * Callback for handling most PHP errors. Compile time and fatal errors will not be caught.
 * Function/method must be defined and included before initializing the system config.
 */
'error_handler'		=> array('System', 'handleError'),
/**
 * Callback for handling script shutdown.
 * Function/method must be defined and included before initializing the system config.
 */
'shutdown_handler'	=> array('System', 'handleShutdown'),

/**
 * Controllers directory.
 */
'controllers.dir'	=> APPLICATION_DIR . '/controllers',
/**
 * Default controllers. Keys are module names. Submodules are separated by slashes. Eg: admin/user
 * WARNING! Controller names are case-sensitive and must denote a valid class name without its suffix.
 */
'controllers.default'	=> array(
							'Index'
						),
/**
 * Models directory
 */
'models.dir'	=> APPLICATION_DIR . '/models'
);

return $config;
?>
