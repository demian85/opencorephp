<?php

//namespace net;

/**
 * @package net
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class URL
{
	/**
	 * @var string
	 */
	private $_url;

	/**
	 * Build query string from associative array.
	 * Values will be encoded using urlencode().
	 * 
	 * @param array $params
	 * @return string
	 */
	public static function toQueryParams(array $params)
	{
		$qs = array();
		foreach ($params as $k => $v) {
			$qs[] = "$k=" . urlencode($v);
		}
		return implode('&', $qs);
	}

	/**
	 * Create relative URL based on the current one and merge or replace query params.
	 * @param array $params
	 * @param boolean $merge Merge or replace GET parameters
	 * @return URL
	 */
	public static function fromQueryParams(array $params, $merge = true)
	{
		$_pos = strrpos($_SERVER['REQUEST_URI'], '?');
		$path = ($_pos === false) ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $_pos);
		$path .= '?' . http_build_query($merge ? array_merge($_GET, $params) : $params);
		return new self($path);
	}

	/**
	 * Create absolute URL from parts.
	 * If protocol or domain are omitted, the default values will be used.
	 *
	 * @param string $protocol
	 * @param string $domain
	 * @param string $path
	 * @param string|mixed[] $queryString
	 * @return URL
	 */
	public static function fromParts($protocol, $domain, $path = '', $queryString = null)
	{
		if (!$protocol) $protocol = Request::getInstance()->getProtocol();
		if (!$domain) $domain = Request::getInstance()->getDomain();
		$url = $protocol . '://' . $domain . $path;
		if (is_array($queryString)) $url .= '?' . self::toQueryParams($queryString);
		else if ($queryString) $url .= '?' . $queryString;
		return new self($url);
	}
	
	/**
	 * Build a relative URL with supplied data array. Numeric keys will be discarded.
	 * If $params es empty, an empty string is returned.
	 * Returned URL always have a slash as its first character.
	 *
	 * @param mixed[] $params
	 * @param string|mixed[] $queryString Query string as an array or string
	 * @return URL
	 */
	public static function fromParams(array $params, $queryString = '')
	{
		$url = '';
		foreach ($params as $name => $value) {
			if (is_numeric($name)) {
				$url .= "/$value";
			}
			else {
				$url .= "/$name:$value";
			}
		}
		
		if (is_array($queryString) && !empty($queryString)) {
			$url .= '/?' . self::toQueryParams($queryString);
		}
		else if ($queryString) {
			$url .= '/?' . $queryString;
		}

		return new self($url);
	}
	
	/**
	 * Create a absolute URL from subdomain labels.
	 *
	 * @param string|string[] $subdomainLabels
	 * @param string|mixed[] $params An array of params or a string separated by slashes
	 * @param string|mixed[] $queryString
	 * @return URL
	 */
	public static function fromSubdomain($subdomainLabels = null, $params = array(), $queryString = '')
	{
		$config = Config::getInstance();
		$request = Request::getInstance();
		$url = $request->getProtocol() . '://';
		if ($subdomainLabels) {
			$url .= (is_array($subdomainLabels) ? implode(".", $subdomainLabels) : $subdomainLabels) . '.';
		}
		$url .= $config->get('core.domain');

		if (is_array($params)) $url .= self::fromParams($params);
		else $url .= "/" . ltrim($params, '/');

		if (is_array($queryString) && !empty($queryString)) $url .= '?' . self::toQueryParams($queryString);
		else if ($queryString) $url .= '?' . $queryString;

		return new self($url);
	}
	
	/**
	 * Build an absolute URL dinamically with the supplied parameters.
	 * Language redirect feature and subdomain map is taken into account.
	 * The route is translated according to {routes.route_map}
	 *
	 * @param string $language The language code that will be injected into the URL depending on the {routes.language_redirect} config value
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 * @param string|mixed[] $queryString
	 * @return URL
	 */
	public static function build($language, $module, $controller, $action = null,
									array $params = array(), $queryString = '')
	{
		$config = Config::getInstance();
		$request = Request::getInstance();
		
		$subdomainLabels = '';
		if (!$language) $language = $config->get('app.language');
		$langRedirect = $config->get('routes.language_redirect');
		$subdomainString = array_search($module, $config->get('routes.subdomain_map'));

		$routeParts = array();
		if ($controller) $routeParts[] = self::formatName($controller);
		if ($action) $routeParts[] = self::formatName($action);

		if ($subdomainString !== false) {
			$subdomainLabels .= $subdomainString . '.';
		}
		else if ($module) {
			array_unshift($routeParts, $module);
		}

		$route = Router::translate(implode('/', $routeParts), true, $language);

		if ($langRedirect == 'subdomain') {
			$subdomainLabels .= $language . '.';
		}
		else if ($langRedirect == 'param') {
			$route = $language . '/' . $route;
		}
		
		$url = $request->getProtocol() . "://" . $subdomainLabels . $config->get('core.domain')
				. '/' . $route . self::fromParams($params, $queryString);
		
		return new self($url);
	}

	/**
	 * Format name to fit URL conventions.
	 * Eg: userPanel is converted to user-panel
	 *
	 * @param string $name
	 * @return string
	 */
	public static function formatName($name)
	{
		return strtolower(preg_replace('#([a-z])([A-Z])#', '$1-$2', $name));
	}

	/**
	 * Translate route for the specified language.
	 * If language is NULL, {app.language} will be used.
	 * Do not include the language code in the route if {routes.language_redirect} is 'param'.
	 * Absolute URL's are not translated in any way.
	 * {routes.subdomain_map} is not supported!
	 *
	 * @param string $route
	 * @param string $language
	 * @return string
	 */
	public static function translate($route, $language = null)
	{
		if (strpos($route, '/') !== 0) return $route;
		
		$config = Config::getInstance();
		$request = Request::getInstance();
		
		if (!$language) $language = $config->get('app.language');
		
		$route = Router::translate(ltrim($route, '/'), true, $language);
		
		$langRedirect = $config->get('routes.language_redirect');
		if ($langRedirect == 'param') {
			$url = '/' . $language . '/' . $route;
		}
		else if ($langRedirect == 'subdomain') {
			$url = $request->getProtocol() . "://" . $language . '.' . $config->get('core.domain') . '/' . $route;
		}
		else {
			$url = "/$route";
		}
		
		return $url;
	}

	/**
	 * Constructor.
	 *
	 * @param string $url
	 */
	public function __construct($url)
	{
		$this->_url = rtrim($url, '/\\');
	}
	
	/**
	 * Returns the URL as a string
	 *
	 * @return string
	 */
	public function getURL()
	{
		return $this->_url;
	}
	
	/**
	 * Returns this URL as a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_url;
	}
}

?>
