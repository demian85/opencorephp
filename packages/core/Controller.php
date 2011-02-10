<?php

//namespace core;

import('net.URL');

/**
 * Represents an abstract controller.
 * Each application controller must extend this class and define its own action methods.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class Controller
{
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var Config
	 */
	protected $config;
	/**
	 * @var View
	 */
	protected $view = null;
	/**
	 * @var string
	 */
	public $defaultAction;
	
	/**
	 * Constructor.
	 *
	 * @param string $defaultAction
	 * @throws InvalidArgumentException if $defaultAction is invalid.
	 */
	protected function __construct($defaultAction = 'default')
	{
		$actionMethod = $defaultAction . 'Action';
		if (!method_exists($this, $actionMethod)) {
			throw new InvalidArgumentException("Invalid default action. Method ".get_class($this)."::{$actionMethod} does not exist.");
		}
		$this->defaultAction = $defaultAction;
		$this->request = Request::getInstance();
		$this->config = Config::getInstance();
	}
	
	/**
	 * Redirect to the specified action. If NULL, default action will be used.
	 * {routes.route_map} is taken into account.
	 * 
	 * @param string $action
	 * @param mixed[] $arguments
	 * @return void
	 */
	protected function _forward($action = null, array $arguments = array())
	{
		if (!$action && empty($arguments)) {
			$this->redirect($this->getURL());
		}
		else {
			$router = Router::getInstance();
			$url = URL::build($router->getRequestedLanguage(), $router->getModule(),
								$this->getName(), $action,
								array_merge($arguments, $this->request->getNamedParams()), $_GET);
			$this->redirect($url);
		}
	}
	
	/**
	 * Get the controller URL.
	 * If requested URI is an alias, the original URI will be returned.
	 * {routes.route_map} is taken into account.
	 *
	 * @return string
	 */
	public function getURL()
	{
		$router = Router::getInstance();
		$url = URL::build($router->getRequestedLanguage(), $router->getModule(), $this->getName());
		return $url;
	}
	
	/**
	 * Default action. It does nothing useful, unless you overload it.
	 *
	 */
	public function defaultAction() { }
	
	/**
	 * Called when the requested action is invalid.
	 * 
	 * @param string $action The requested inexistent action.
	 * @param mixed[] $arguments The arguments passed to the inexistent method.
	 * @return void
	 */
	public function actionError($action, array $arguments)
	{
		$this->_forward(null, $arguments);
	}

	/**
	 * Called when the requested controller does not exist.
	 * This method should be overloaded for the default controller within the requested module.
	 *
	 * @param string $controller The requested inexistent controller without formatting.
	 * @param array $params Additional url params
	 * @return void
	 */
	public function controllerError($controller, array $params)
	{
		$this->_forward(null, $params);
	}
	
	/**
	 * Send a redirect header and exit script.
	 *
	 * @param string $url
	 * @param boolean $translateRoute if TRUE, the URL will be automatically translated according to {routes.route_map} if it starts with a slash.
	 * @param int $httpCode
	 * @return void
	 */
	public function redirect($url, $translateRoute = true, $httpCode = 302)
	{
		if ($translateRoute) $url = URL::translate($url);
		header("Location: $url", true, $httpCode);
		exit;
	}

	/**
	 * Magic method for inexistent methods.
	 *
	 * @param string $method
	 * @param mixed[] $arguments
	 */
	public function __call($method, array $arguments)
	{
		throw new BadMethodCallException("Method '".__CLASS__."::$method' not found.");
	}
	
	/**
	 * Returns the controller name.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}
	
	/**
	 * Get controller's name.
	 *
	 * @return string
	 */
	final public function getName()
	{
		return substr(get_class($this), 0, -10);
	}
}
?>