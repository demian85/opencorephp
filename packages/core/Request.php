<?php

//namespace core;

/**
 * This class handles the request. Provides useful methods for fetching URL parameters.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Request
{
	/**
	 * Named parameters indices.
	 * @var int[]
	 */
	private $namedParamsIndices = array();
	/**
	 * @var Request
	 * @static
	 */
	protected static $_instance = null;
	/**
	 * Array containing both indexed and named parameters.
	 * @var string[]
	 */
	protected $_params = array();
	/**
	 * Array containing indexed parameters.
	 * @var string[]
	 */
	protected $_indexedParams = array();
	/**
	 * Array containing named parameters.
	 * @var string[]
	 */
	protected $_namedParams = array();

	/**
	 * Constructor. Analize requested URI and build parameters.
	 *
	 */
	protected function __construct()
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			$_pos = strrpos($_SERVER['REQUEST_URI'], '?');
			if ($_pos === false) {
				$_params = $_SERVER['REQUEST_URI'];
			}
			else {
				$_params = substr($_SERVER['REQUEST_URI'], 0, $_pos);
			}

			$params = preg_split("#/#", $_params, -1, PREG_SPLIT_NO_EMPTY);

			// TODO: use lambda
			array_walk($params, array($this, "_fetchNamedParameters"));
			foreach ($this->namedParamsIndices as $i) {
				unset($params[$i]);
			}

			$this->_indexedParams = array_values($params);
			$this->_params = array_merge($this->_indexedParams, $this->_namedParams);
		}
	}

	/**
	 * Callback. Checks if $value is named.
	 *
	 * @param string $value
	 * @param int $index
	 * @return void
	 */
	private function _fetchNamedParameters($value, $index)
	{
		$pos = strpos($value, ":");
		if (!$pos) return;
		$tmp = explode(":", $value);
		$this->_namedParams[$tmp[0]] = (isset($tmp[1]) ? $tmp[1] : "");
		$this->namedParamsIndices[] = $index;
	}

	/**
	 * Get instance of this class using Singleton pattern.
	 *
	 * @return Request
	 */
	public static function getInstance()
	{
		if (self::$_instance == null) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Get requested parameters.
	 *
	 * @return string[]
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Get requested numerically indexed parameters.
	 *
	 * @return string[]
	 */
	public function getIndexedParams()
	{
		return $this->_indexedParams;
	}

	/**
	 * Get requested named parameters.
	 *
	 * @return string[]
	 */
	public function getNamedParams()
	{
		return $this->_namedParams;
	}

	/**
	 * Get parameter by its index or key.
	 * Optionally return a default value if parameter does not exist.
	 *
	 * @param string|int $index
	 * @param mixed $default
	 * @return string
	 */
	public function getParam($index, $default = null)
	{
		return isset($this->_params[$index]) ? $this->_params[$index] : $default;
	}

	/**
	 * Get requested route. It's the result of joining the numerically indexed parameters.
	 * Eg: When the requested URI is "/admin/users/lang:es?var=1&query=hello", the route will be /admin/users
	 * The returned string is always lowercase.
	 *
	 * @return string
	 */
	public function getRoute()
	{
		return strtolower(implode("/", $this->getIndexedParams()));
	}

	/**
	 * Detects if the request was made via AJAX.
	 * Checks for a header named "HTTP_X_REQUESTED_WITH" with the value "XMLHttpRequest".
	 *
	 * @return boolean
	 */
	public function isAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Detect if request method is POST.
	 *
	 * @return boolean
	 */
	public function isPost()
	{
		return strtoupper($_SERVER['REQUEST_METHOD']) == 'POST';
	}

	/**
	 * Check if script has been invoked via web request.
	 *
	 * @return boolean
	 */
	public function isWebRequest()
	{
		return isset($_SERVER) && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']);
	}

	/**
	 * Check if script has been invoked via command line interface (CLI)
	 *
	 * @return boolean
	 */
	public function isCLI()
	{
		return isset($_SERVER['SHELL']);
	}

	/**
	 * Get the requested protocol (lowercase).
	 *
	 * @return string
	 */
	public function getProtocol()
	{
		return (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == 'on') ? 'https' : 'http';
	}

	/**
	 * Get the referral URL
	 *
	 * @return string
	 */
	public function getReferer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}

	/**
	 * Checks if the referer is from a specific domain (fully or partially).
	 * If null, checks the current domain.
	 *
	 * @param string $domain
	 * @return boolean
	 */
	public function isRefererFromDomain($domain = null) {
		if (!$domain) $domain = $this->getDomain();
		return stripos($this->getReferer(), $domain) !== false;
	}

	/**
	 * Get current domain. Does not use {core.domain}, so subdomains cannot be excluded.
	 * If domain cannot be determined, NULL is returned.
	 *
	 * @return string
	 */
	public function getDomain()
	{
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
				: (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME']
					: (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null));
	}

	/**
	 * Get current URL
	 *
	 * @param boolean $includePath include the path part of the URL
	 * @param boolean $includeQueryString include query string
	 * @return string
	 */
	public function getURL($includePath = true, $includeQueryString = true)
	{
		$base = $this->getProtocol() . '://' . $this->getDomain();

		$_pos = strrpos($_SERVER['REQUEST_URI'], '?');
		$path = ($_pos === false) ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $_pos);
		if ($includePath) $base .= $path;
		if ($includeQueryString) {
			$qs = (isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] :
				($_pos !== false ? substr($_SERVER['REQUEST_URI'], $_pos + 1) : ''));
			if ($qs) $base .= '?' . $qs;
		}
		return $base;
	}

	/**
	 * Get the subdomain labels from the current URI.
	 *
	 * @return string[]
	 */
	public function getSubdomainLabels()
	{
		$config = Config::getInstance();
		return preg_split("#\\.#", str_replace(
					$config->get('core.domain'), '', $this->getDomain()), -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Get specific subdomain label.
	 *
	 * @param int $index
	 * @return string
	 */
	public function getSubdomainLabel($index)
	{
		$_tmp = $this->getSubdomainLabels();
		return isset($_tmp[$index]) ? $_tmp[$index] : null;
	}

	/**
	 * Returns the requested URI without GET parameters as a string.
	 * The order of the parameters may not be the same as $_SERVER['REQUEST_URI'] because named params are always appended at the end.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return implode("/", $this->getParams());
	}
}
?>