<?php

//namespace core;

import('net.URL');

/**
 * Main controller. Parses the request and gives control to corresponding controller.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
final class Router
{
	/**
	 * @var Router
	 * @static
	 */
	private static $instance = null;
	/**
	 * Indicates the dispatch() method to throw exceptions.
	 * @var boolean
	 */
	private $_throwExceptions = true;
	/**
	 * @var string
	 */
	private $_controllerPath = null;
	/**
	 * Default controller(s).
	 * @var string|string[]
	 */
	private $_defaultController;
	/**
	 * Request instance.
	 * @var Request
	 */
	private $_request;
	/**
	 * Config instance.
	 * @var Config
	 */
	private $_config;
	/**
	 * @var string
	 */
	private $_fullControllerName = null;
	/**
	 * Requested module.
	 * @var string
	 */
	private $_currentModule = null;
	/**
	 * Requested controller.
	 * @var Controller
	 */
	private $_currentController = null;
	/**
	 * Requested action.
	 * @var string
	 */
	private $_currentAction = null;
	/**
	 * Requested action parameters.
	 * @var mixed[]
	 */
	private $_currentParams = null;
	/**
	 * Requested route string.
	 * @var string
	 */
	private $_route = null;
	/**
	 * Directory of requested controller.
	 * @var string
	 */
	private $_currentDir = null;
	/**
	 * Indicates if request has been dispatched.
	 * @var boolean
	 */
	private $_dispatched = false;
	/**
	 * If the language redirect feature is enabled, this will contain the requested language.
	 * @var string
	 */
	private $_uriLanguage = null;
	/**
	 * Registered events
	 * @var mixed[]
	 */
	private $_events = array(
		'beforedispatch'	=> array(),
		'afterdispatch'		=> array(),
		'moduleload'		=> array(),
		'controllerload'	=> array()
	);
	/**
	 * Registered events
	 * @var mixed[]
	 */
	private $_moduleListeners = array();

	/**
	 * Constructor. Initialize main configuration, build route and set locale.
	 *
	 * @throws IOException if unable to read configuration file.
	 * @throws FileNotFoundException if controllers directory is not valid.
	 */
	private function __construct()
	{
		$this->_config = Config::getInstance();
		$this->_request = Request::getInstance();
		$this->setControllerPath($this->_config->get("core.controllers.dir"));
		$this->setDefaultController($this->_config->get('core.controllers.default'));
	}

	/**
	 * Get default controller for specified module. Modules are subfolders inside the controllers directory.
	 *
	 * @param string $module null indicates no module. Modules are separated by slashes. Eg: admin/users
	 * @return string controller name without its suffix. Eg: Users
	 */
	private function _getDefaultController($module = null)
	{
		if (is_array($this->_defaultController)) {
			if ($module == null || $module != null && !isset($this->_defaultController[$module])) {
				$c = $this->_defaultController[0];
			}
			else if (isset($this->_defaultController[$module])) {
				$c = $this->_defaultController[$module];
			}
		}
		else {
			$c = $this->_defaultController;
		}
		return $c;
	}

	/**
	 * Format name to fit class and method naming conventions.
	 *
	 * @param string $name
	 * @param string $ucFirst Format name to fit class naming conventions
	 * @return string
	 */
	private function _formatName($name, $ucFirst = true)
	{
		$parts = preg_split("#(?<=[a-zA-Z0-9])[_-](?=[a-zA-Z0-9])#", strtolower($name), -1, PREG_SPLIT_NO_EMPTY);
		$str = "";
		foreach ($parts as $part) {
			$str .= ucfirst($part);
		}
		$fname = (!$ucFirst) ? (strtolower(substr($str, 0, 1)) . substr($str, 1)) : $str;

		return $fname;
	}

	/**
	 * Execute specified registered event.
	 * If more than one argument if passed, those are sent to the registered callback.
	 *
	 * @param string $name
	 * @return void
	 */
	private function _triggerEvent($name /*, ... */)
	{
		if (!empty($this->_events[$name])) {
			$args = func_get_args();
			unset($args[0]);
			foreach ($this->_events[$name] as $func) {
				call_user_func_array($func, $args);
			}
		}
	}

	/**
	 * Iterate through each loaded module and trigger event.
	 *
	 * @param string[] $modules
	 * @return void
	 */
	private function _triggerModuleLoad(array $modules)
	{
		// module load events
		$ms = array();
		foreach ($modules as $_m) {
			$ms[] = $_m;
			$mstr = implode('/', $ms);
			$this->_triggerEvent('moduleload', $mstr);
			if (isset($this->_moduleListeners[$mstr])) {
				foreach ($this->_moduleListeners[$mstr] as $_c) {
					call_user_func($_c);
				}
			}
		}
	}

	/**
	 * Parse request URI, resolve aliases and build final route.
	 *
	 * @return string[] Route parts as an array
	 */
	private function _buildRoute()
	{
		$startIndex = $this->_config->get('routes.start_index');
		$langRedirect = $this->_config->get('routes.language_redirect');
		$subdomainMap = $this->_config->get('routes.subdomain_map');

		$indexedParams = $this->_request->getIndexedParams();

		$subdomainParts = array();
		$pathParts = array();

		// check language redirect for subdomain
		if (!empty($subdomainMap) || $langRedirect) {
			$subdomainLabels = $this->_request->getSubdomainLabels();

			if ($langRedirect == 'subdomain') {
				$lang = array_pop($subdomainLabels);
			}
		}

		// search submodule map and find a match, then add module to the route
		if (!empty($subdomainMap)) {
			$labelStr = implode(".", $subdomainLabels);
			foreach ($subdomainMap as $_k => $_v) {
				if ($_k == $labelStr) {
					$subdomainParts = explode("/", $_v);
					break;
				}
			}
		}

		// resolve aliases
		$aliases = $this->_config->get('routes.aliases');
		if (!empty($aliases)) {
			$uri = $this->_request->getRoute();
			$count = 0;
			foreach ($aliases as $_regexp => $_replace) {
				$uri = preg_replace($_regexp, $_replace, $uri, -1, $count);
				if ($count > 0) break;
			}
			$pathParts = explode('/', ltrim($uri, "\\/"));
		}
		else {
			$pathParts = $indexedParams;
		}

		if ($startIndex > 0) {
			$pathParts = array_slice($pathParts, $startIndex);
		}

		if ($langRedirect) {

			if ($langRedirect == 'param') {
				$lang = array_shift($pathParts);
			}

			if (!$lang || !in_array($lang, array_keys($this->_config->get('i18n.language_map')))) {
				$lang = Lang::getLanguageByCountry(Client::getCountryCode());

				if ($langRedirect == 'param' && !empty($indexedParams)) {
					$uri = ($langRedirect == 'subdomain') ?
								URL::fromSubdomain($lang, $this->_request->getParams()) : "/$lang";
					header("Location: $uri");
					exit;
				}
			}

			$this->_uriLanguage = $lang;
		}

		return array_merge($subdomainParts, $pathParts);
	}

	/**
	 * Check {routes.route_map} and get translation for specified module, controller or action name.
	 * If no translation is found, the same route is returned.
	 * Returns an array if original route is an array.
	 *
	 * @param string|string[] $route
	 * @param boolean $reverse Reverse translation
	 * @param string $language
	 * @return string|string[]
	 */
	public static function translate($route, $reverse = false, $language = null)
	{
		$config = Config::getInstance();
		$routeMap = $config->get('routes.route_map');

		if (!$language) $language = $config->get('app.language');

		$routes = isset($routeMap[$language]) ? $routeMap[$language] : null;

		if (!empty($routes)) {
			$similar = array();
			$validRoutes = array();
			$routeString = is_array($route) ? strtolower(implode('/', $route)) : strtolower($route);
			foreach ($routes as $k => $v) {
				$_v = $reverse ? $k : $v;
				if (strpos($routeString, $_v) !== 0) continue;
				$similar[] = similar_text($_v, $routeString);
				$validRoutes[] = array($k, $v);
			}

			if (!empty($validRoutes)) {
				array_multisort($similar, SORT_NUMERIC, SORT_DESC, $validRoutes);
				$translation = $reverse ? str_replace($validRoutes[0][0], $validRoutes[0][1], $routeString)
								: str_replace($validRoutes[0][1], $validRoutes[0][0], $routeString);

				if (is_array($route)) {
					$translation = explode('/', $translation);
				}
			}
			else {
				$translation = $route;
			}
		}
		else {
			$translation = $route;
		}

		return $translation;
	}

	/**
	 * Get instance of this class.
	 *
	 * @return Router
	 * @throws IOException if unable to read configuration file.
	 * @throws FileNotFoundException if controllers directory is not valid.
	 */
	public static function getInstance()
	{
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Dispatch request. This method should be invoked only once. Further calls will be ignored.
	 *
	 * @return void
	 * @throws FileNotFoundException if the file which contains the controller class definition could not be found.
	 */
	public function dispatch()
	{
		if ($this->_dispatched) return;

		$this->_dispatched = true;

		try {

			$route = $this->_buildRoute();

			if ($this->_config->get('routes.language_redirect')) {
				// set locale based on requested language
				$localeInfo = Client::getLocaleInfo(Router::getInstance()->getRequestedLanguage());
				$locale = $localeInfo['locale'];
				$this->_config->setLocale($locale);
			}

			$params = self::translate($route);
			$this->_route = (empty($params) ? '' : '/') . strtolower(implode('/', $params));

			$controller = array_shift($params);

			if ($controller == null) {
				$_format = false;
				$controller = $this->_getDefaultController();
			}
			else {
				$_format = true;
			}

			// check recursively if folder exists and search for controller inside it
			$tmpFile = $this->_controllerPath . DIRECTORY_SEPARATOR . $controller;

			$moduleParts = array();

			while (is_dir($tmpFile)) {

				$moduleParts[] = $controller;
				$tmp = array_shift($params);

				if ($tmp != null) {
					$controller = $tmp;
				}
				else {
					$controller = $this->_getDefaultController(basename($tmpFile));
					$_format = false;
				}

				$tmpFile .= DIRECTORY_SEPARATOR . $controller;
			}

			$this->_currentModule = implode('/', $moduleParts);

			$unformattedController = $controller;
			if ($_format) {
				$controller = $this->_formatName($controller);
			}

			$this->_fullControllerName = $controller . "Controller";
			$this->_currentDir = dirname($tmpFile);
			$controllerFilePath = $this->_currentDir . DIRECTORY_SEPARATOR . $this->_fullControllerName . ".php";

			if (!file_exists($controllerFilePath)) {

				// get default controller
				$defController = $this->_getDefaultController($this->_currentModule);
				$this->_fullControllerName = $defController . "Controller";
				$controllerFilePath = $this->_currentDir . DIRECTORY_SEPARATOR . $this->_fullControllerName . ".php";

				// controller should be there!
				if (!file_exists($controllerFilePath)) {
					throw new ClassNotFoundException("Unable to initialize controller '$defController'. File '$controllerFilePath' could not be found.");
				}

				$this->_currentAction = "controllerError";
			}

			$this->_triggerEvent('beforedispatch', $this->_route);

			require_once($controllerFilePath);

			// make sure file name is the same as the class name
			if (!class_exists($this->_fullControllerName, false)) {
				throw new ClassDefNotFoundException("Class '{$this->_fullControllerName}' not found.");
			}

			$this->_triggerModuleLoad($moduleParts);

			$this->_currentController = new $this->_fullControllerName;

			// make sure we have an instance of Controller class
			if (!$this->_currentController instanceof Controller) {
				throw new RuntimeException("Class '{$this->_fullControllerName}' does not extend from 'Controller' class.");
			}

			$this->_triggerEvent('controllerload', $this->_currentController);

			if ($this->_currentAction != null) {

				// controller error
				$this->_currentController->controllerError($unformattedController, $params);
				$this->_currentParams = $params;
			}
			else {

				if (isset($params[0]) && is_numeric($params[0])) {
					$action = $this->_currentController->defaultAction;
				}
				else {
					$action = array_shift($params);
					if (!$action) {
						$action = $this->_currentController->defaultAction;
					}
				}

				$formattedAction = $this->_formatName($action);

				$this->_currentParams = $params;
				$actionMethod = $formattedAction . 'Action';

				if (!method_exists($this->_currentController, $actionMethod)) {
					$this->_currentAction = $action;
					$this->_currentController->actionError($action, $this->_currentParams);
				}
				else {
					$this->_currentAction = $formattedAction;
					call_user_func_array(array($this->_currentController, $actionMethod), $this->_currentParams);
				}
			}

			$this->_triggerEvent('afterdispatch', $this->_route);

		} catch (Exception $ex) {
			if ($this->_config['logs.log_exceptions'] && !($ex instanceof LoggerException)) {
				import('log.Logger');
				Logger::getInstance()->log($ex, Logger::TYPE_EXCEPTION);
			}
			if ($this->_throwExceptions) throw $ex;
		}
	}

	/**
	 * Register a callback for a specific event.
	 * Valid events:
	 * - beforedispatch : executed after the main initialization, just before dispatching and initializing the controller.
	 *						The final translated route will be passed as the first parameter.
	 * - afterdispatch : executed just after dispatching. The final translated route will be passed as the first parameter.
	 * - moduleload : executed for each module before controller instantiation.
	 *					The module name is passed as the first parameter. Eg: admin, admin/users
	 * - controllerload : executed when the controller has been instantiated but the action has not been called yet.
	 *					The controller instance is passed as the first parameter.
	 *
	 * @param string $name
	 * @param callback $callback
	 */
	public function addEventListener($name, $callback)
	{
		if (!array_key_exists($name, $this->_events)) {
			throw new InvalidArgumentException("\"$name\" is not a valid event name.");
		}
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Invalid callback supplied.");
		}
		$this->_events[$name][] = $callback;
	}

	/**
	 * Add load event listener for specific module.
	 *
	 * @param string $module
	 * @param callback $callback
	 */
	public function addModuleLoadListener($module, $callback)
	{
		if (!is_callable($callback)) {
			throw new InvalidArgumentException("Invalid callback supplied.");
		}
		if (!isset($this->_moduleListeners[$module])) {
			$this->_moduleListeners[$module] = array();
		}
		$this->_moduleListeners[$module][] = $callback;
	}

	/**
	 * Sets default controller(s). It accepts a string or an array as the first parameter.
	 * If an array is supplied, the keys represents modules and the values the default controller for that module.
	 * Modules are sub-folders inside the controllers directory.
	 *
	 * @param string|string[] $name
	 * @return void
	 */
	public function setDefaultController($name)
	{
		$this->_defaultController = $name;
	}

	/**
	 * Sets the controllers directory.
	 *
	 * @param string $path
	 * @return void
	 * @throws FileNotFoundException if $path is not a valid directory.
	 */
	public function setControllerPath($path)
	{
		if (!is_dir($path)) {
			import("io.FileNotFoundException");
			throw new FileNotFoundException("Invalid controller path. '$path' directory not found.");
		}
		$this->_controllerPath = rtrim($path, "\\/");
	}

	/**
	 * Enable exceptions to be thrown during the script excecution.
	 *
	 * @param boolean $throw
	 * @return void
	 */
	public function setThrowExceptions($throw = true)
	{
		$this->_throwExceptions = (boolean)$throw;
	}

	/**
	 * Return requested route as a string.
	 * Route is not available before calling the dispatch() method.
	 * Eg: /panel/users/8
	 *
	 * @return string
	 */
	public function getRoute()
	{
		return $this->_route;
	}

	/**
	 * Return requested module.
	 *
	 * @return string
	 */
	public function getModule()
	{
		return $this->_currentModule;
	}

	/**
	 * Return instance of the current controller or NULL if no controller has been instantiated.
	 *
	 * @return Controller
	 */
	public function getController()
	{
		return $this->_currentController;
	}

	/**
	 * Return requested action.
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->_currentAction;
	}

	/**
	 * Return the action parameters.
	 *
	 * @return mixed[]
	 */
	public function getParams()
	{
		return $this->_currentParams;
	}

	/**
	 * Get the requested language code if language redirect feature is enabled or NULL.
	 *
	 * @return string
	 */
	public function getRequestedLanguage()
	{
		return $this->_uriLanguage;
	}
}
?>