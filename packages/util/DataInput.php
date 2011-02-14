<?php

//namespace util;

/**
 * This class initializes, filters and validates values from an input array.
 * By implementing the ArrayAccess interface, you are allowed to access the object input data like an array.
 * 
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class DataInput implements Iterator, ArrayAccess
{
	/**
	 * @var mixed[]
	 */
	protected $_data;
	/**
	 * @var mixed[]
	 */
	protected $_initializedKeys;
	/**
	 * @var string[]
	 */
	protected $_errors = array();
	
	/**
	 * Get specific value depending on its type.
	 *
	 * @param string $type Valid types are: string, int, float, double, array, boolean, object
	 * @param mixed $value
	 * @param callback|callback[] $filter
	 * @return mixed
	 * @throws InvalidArgumentException if $type or $filter is invalid
	 */
	protected function _getValue($type, $value, $filter = null) {
		if ($value === null) return null;
		switch ($type) {
			case 'string':
				$value = (string)$value;
				break;
			case 'int':
				$value = (int)$value;
				break;
			case 'float':
				$value = (float)$value;
				break;
			case 'double':
				$value = (double)$value;
				break;
			case 'boolean':
				$value = (boolean)$value;
				break;
			case 'array':
				$value = is_array($value) ? $value : array($value);
				break;
			case 'object':
				$value = is_object($value) ? $value : (object)$value;
				break;
			default:
				throw new InvalidArgumentException("'$type' is not a valid data type.");
		}
		
		if ($filter) {
			foreach ((array)$filter as $f) {
				if (!is_callable($f)) {
					throw new InvalidArgumentException("'$f' is not a valid callback.");
				}
				$value = $f($value);
			}
		}
		
		return $value;
	}
	
	/**
	 * Check length.
	 *
	 * @param string $length
	 * @param mixed $value
	 * @return boolean
	 */
	protected static function _checkLength($length, $value)
	{
		$valueLength = mb_strlen($value, Config::getInstance()->get('core.encoding'));
		
		if (is_numeric($length) && $valueLength != $length) {
			return false;
		}
		else {
			$tmp = explode(',', $length);
			$minLength = (int)$tmp[0];
			$maxLength = isset($tmp[1]) ? (int)$tmp[1] : null;
			if ($valueLength < $minLength || $maxLength && $valueLength > $maxLength) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Checks if $value is an email. Accepts an array of values.
	 *
	 * @param mixed|mixed[] $value
	 * @return boolean
	 */
	public static function isEmail($value)
	{
		foreach ((array)$value as $v) {
			if (!preg_match("#^[\\w.-]+(?:\\+[\\w.-]+)?@[\\w.-]{1,255}\\.[a-z]{2,3}(?:\\.[a-z]{2})?$#i", $v)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Validate input data.
	 * Examples of valid data types:
	 * <ul>
	 * <li>"string" = any type of string</li>
	 * <li>"email" = email including the "+" character</li>
	 * <li>"date" = date with format yyyy-mm-dd</li>
	 * <li>"datetime" = date and time (optional) with format yyyy-mm-dd[ hh:mm[:ss]]</li>
	 * <li>"time" = time with format hh:mm[:ss]</li>
	 * <li>"phone" = a phone number, includes prefix code in parenthesis, spaces and dashes</li>
	 * <li>"integer"</li>
	 * <li>"number"</li>
	 * <li>"float"</li>
	 * <li>"alphanumeric"</li>
	 * <li>"filename" = a valid Windows file name</li>
	 * <li>"filepath" = a valid file path, includes / and \</li>
	 * <li>"dirname" = a valid directory path, vali separators are / and \</li>
	 * <li>"ip" = IPv4</li>
	 * <li>"url" = URL starting with "http://", "https://", "ftp://" or "www." followed by a domain and extra paremeters</li>
	 * <li>"domain"</li>
	 * <li>"creditcard" = 16 digits</li>
	 * <li>"range" = tests value between the specified range. You must provide a valid range using the options notation. See below</li>
	 * </ul>
	 * If data type is not listed above, it will be considered as a regular expression.
	 * Some data types include extra options between parenthesis after the type name. Eg:
	 * string(1,10) = a string between 1 and 10 characters
	 * string(15) = a string with a fixed length of 15 characters
	 * integer(5,) = an integer with a minimum of 5 characters
	 * date(dd/mm/yyyy) = a custom date format
	 * filepath(jpg,gif,png) = a file with specific extensions
	 * range(1,10) = a number between 1 and 10 inclusive
	 * @param mixed $value
	 * @param string $type Data type. Some include extra options, see examples below.
	 * @return boolean
	 * @example <code>
	 * $input = new DataInput($_POST);
	 * // initialize data
	 * $input->init(array('name', 'user_email', 'user_date'), 'string');
	 * $input->init('age', 'int');
	 * // validate
	 * $input->validate('name', 'string(1,20)', 'Name must have between 1 and 20 characters');
	 * $input->validate('user_email', 'email', 'Invalid email!');
	 * $input->validate('user_date', 'date(dd/mm/yyyy)', 'Invalid date format!');
	 * // check validation and show errors
	 * if (!$input->isValid()) {
	 * 		echo HTML::uList($input->getErrors());		
	 * }
	 * </code>
	 * @throws InvalidArgumentException if any data type option is invalid
	 */
	public static function checkValue($value, $type = 'string')
	{
		if (preg_match('#^(\w+)\((.*?)\)$#', $type, $matches)) {
			$type = $matches[1];
			$typeOptions = $matches[2];
		}
		else {
			$typeOptions = null;
		}
		
		$valid = true;
		
		switch ($type) {
			case "string":
				if (empty($value) || $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "email":
				if (!self::isEmail($value) || $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "date":
				if ($typeOptions) {
					/* custom date format */
					if (!preg_match('#^([a-z]+)[/-]([a-z]+)[/-]([a-z]+)$#i', $typeOptions, $typeMatches)) {
						throw new InvalidArgumentException("'$typeOptions' is not a valid date format.");
					}
					
					if (!preg_match('#^(\d+)[/-](\d+)[/-](\d+)$#', $value, $valueMatches)) {
						$valid = false;
					}
					else {
						$_year = array_search('yyyy', $typeMatches);
						$_month = array_search('mm', $typeMatches);
						$_day = array_search('dd', $typeMatches);
						if ($_year === false || $_month === false || $_day === false 
							|| !checkdate($valueMatches[$_month], $valueMatches[$_day], $valueMatches[$_year])) {
							$valid = false;
						}
					}
				}
				else {
					/* yyyy-mm-dd */
					if (!preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $matches) 
						|| !checkdate($matches[2], $matches[3], $matches[1])) {
							$valid = false;
						}
				}
				break;
			case "datetime":
				/* yyyy-mm-dd[ hh:mm[:ss]] */
				if (!preg_match('#^(\d{4})-(\d{2})-(\d{2})(?:\s(\d{2}):(\d{2})(?::(\d{2}))?)?$#', $value, $matches)) {
					$valid = false;
				}
				if (!checkdate($matches[2], $matches[3], $matches[1]) 
					|| @$matches[4] && $matches[4] > 24 || @$matches[5] && $matches[5] > 59
					|| @$matches[6] && $matches[6] > 59) {
						$valid = false;
					}
				break;
			case "time":
				/* hh:mm[:ss] */
				if (!preg_match('#^(\d{2}):(\d{2})(?::(\d{2}))?$#', $value, $matches)) {
					$valid = false;
				}
				if ($matches[1] > 24 || $matches[2] > 59 || ($matches[3] && $matches[3] > 59)) {
					$valid = false;
				}
				break;
			case "phone":
				if (!preg_match('#^[0-9_+\(\) \-]+$#', $value) 
						|| $typeOptions && !self::_checkLength($typeOptions)) {
					$valid = false;
				}
				break;
			case "int":
			case "integer":
				if (!preg_match('#^\d+$#', $value)
						|| $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "number":
				if (!is_numeric($value) || $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "float":
				if (!preg_match('#^\d+(\.?\d+)?$#', $value)
						|| $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "alphanumeric":
				if (!preg_match('#^\w+$#i', $value) || $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "filename":
				if (!preg_match("/^[^?*:|<>\\\\\\/]+\\.([a-z0-9]+)$/i", $value, $matches)) {
					$valid = false;
				}
				if ($typeOptions) {
					$ext = array_map('strtolower', explode(',', $typeOptions));
					if (count($ext) > 0 && !in_array(strtolower($matches[1]), $ext)) {
						$valid = false;
					}
				}
				break;
			case "filepath":
				if (!preg_match("/^(?:[a-z]:[\\\\\\/])?[^?*:|<>]+\\.([a-z0-9]+)$/i", $value, $matches)) {
					$valid = false;
				}
				if ($typeOptions) {
					$ext = array_map('strtolower', explode(',', $typeOptions));
					if (count($ext) > 0 && !in_array(strtolower($matches[1]), $ext)) {
						$valid = false;
					}
				}
				break;
			case "dirname":
				if (!preg_match("/^[^?*:|<>]+$/i", $value)) {
					$valid = false;
				}
				break;
			case 'ip':
			case "ipv4":
				if (!preg_match("/^(\\d{1,3})\\.(\\d{1,3})\\.(\\d{1,3})\\.(\\d{1,3})$/i", $value, $matches)) {
					$valid = false;
				}
				if ($matches[1] > 255 || $matches[2] > 255 || $matches[3] > 255 || $matches[4] > 255) {
					$valid = false;
				}
				break;
			case "url":
				if (!preg_match("/((^(http|https|ftp):\\/\\/)|(^www\\.)).+?\\.[a-z]{2,3}.*?$/i", $value)
						 || $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "domain":
				if (!preg_match("/^([\\w-]+\\.(?!\\.))+[a-z]{2,3}$/i", $value)
						|| $typeOptions && !self::_checkLength($typeOptions, $value)) {
					$valid = false;
				}
				break;
			case "creditcard":
				if (!preg_match("#^\\d{16}$#", $value)) {
					$valid = false;
				}
				break;
			case "range":
				$tmp = explode(',', $typeOptions);
				if (count($tmp) < 2) {
					throw new InvalidArgumentException("Invalid range boundaries. You must provide a minimum and maximum length.");
				}
				if ($value < $tmp[0] || $value > $tmp[1]) {
					$valid = false;
				}
				break;
			default:
				if (!preg_match($type, $value)) {
					$valid = false;
				}
		}
		
		return $valid;
	}
	
	/**
	 * Constructor.
	 *
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		$this->_data = $data;
	}
	
	/**
	 * Validate input data previously initialized and associate an error message with its key.
	 *
	 * @param string|string[] $key A single key or array of keys
	 * @param string $type
	 * @param string|string[] $errorMessage Error message that will be associated with the key in case it's invalid. Accepts a single string or an array of strings, in which case will be associated with its corresponding key
	 * @return boolean
	 * @see #checkValue
	 */
	public function validate($key, $type = 'string', $errorMessage = null)
	{
		$valid = true;
		$count = 0;
		foreach ((array)$key as $k) {
			if (!isset($this->_data[$k])) $this->_data[$k] = '';
			$value = $this->_data[$k];
			if (!self::checkValue($value, $type)) {
				$valid = false;
				if ($errorMessage != null) {
					$this->_errors[$k] = is_array($errorMessage) ? $errorMessage[$count++] : $errorMessage;
				}
				break;
			}
		}
		
		return $valid;
	}
	
	/**
	 * Initialize values.
	 *
	 * @param string|string[] $key A single key or an array of keys
	 * @param string $type Value data type
	 * @param mixed $defaultValue Default value if key does not exist in the input array
	 * @param callback|callback[] $filter A single callback or array of callbacks. The return value of each call will be assigned to the original value.
	 */
	public function init($key, $type = 'string', $defaultValue = '', $filter = null)
	{
		foreach ((array)$key as $k) {
			if (!isset($this->_data[$k])) {
				$this->_data[$k] = $this->_getValue($type, $defaultValue);
			}
			else {
				$this->_data[$k] = $this->_getValue($type, $this->_data[$k], $filter);
			}
			$this->_initializedKeys[] = $k;
		}
	}
	
	/**
	 * Check if data is valid.
	 * Must be called after validating each entry.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return empty($this->_errors);
	}
	
	/**
	 * Get error list
	 *
	 * @return string[]
	 */
	public function getErrors()
	{
		return $this->_errors;
	}
	
	/**
	 * Get the original data array with the initialized values.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * Get the initialized values as an associative array.
	 *
	 * @return array
	 */
	public function getInitializedValues()
	{
		$values = array();
		foreach ($this->_initializedKeys as $k) {
			$values[$k] = $this->_data[$k];
		}
		return $values;
	}
	
	/**
	 * Get specific value or NULL if key does not exist.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getValue($key)
	{
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}
	
	/**
	 * Iterator:valid()
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return current($this->_data) !== false;
	}
	
	/**
	 * Iterator::current()
	 *
	 * @return mixed
	 */
	public function current()
	{
		return current($this->_data);
	}
	
	/**
	 * Iterator::rewind()
	 * 
	 * @return void
	 */
	public function rewind()
	{
		reset($this->_data);
	}
	
	/**
	 * Iterator::next()
	 *
	 * @return void
	 */
	public function next()
	{
		next($this->_data);
	}
	
	/**
	 * Iterator::key()
	 *
	 * @return mixed
	 */
	public function key()
	{
		return key($this->_data);
	}
	
	/**
	 * ArrayAccess::offsetSet()
	 *
	 * @param string $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->_data[$offset] = $value;
	}
	
	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
    {
		return isset($this->_data[$offset]);
	}
	
	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset)
    {
		unset($this->_data[$offset]);
	}
	
	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}
}
?>
