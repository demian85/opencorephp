<?php

//namespace util;

/**
 * This class represents a persistent set of properties.
 * Properties can be loaded from php or ini files.
 * For PHP files, you must declare and return an array whose keys map to php values.
 * You can later access those values using the file name as a prefix followed by a dot and the array key.
 * Example: 
<code>
$properties = new Properties();
$properties->load('file.php');
$value = $properties->get('file.key');
</code>
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Properties implements ArrayAccess
{
	/**
	 * @var mixed[]
	 */
	protected $data = array();
	/**
	 * @var boolean
	 */
	protected $fileNameAsPrefix;
	/**
	 * @var string[]
	 */
	protected $_loadedFiles = array();
	
	/**
	 * Constructor.
	 *
	 * @param Properties|mixed[] $defaults Default properties. It should be a Properties object or an array.
	 * @param boolean $fileNameAsPrefix Use file name as a key prefix for all the values in that file.
	 * @throws InvalidArgumentException if $defaults is not a Properties object or an array.
	 */
	public function __construct($defaults = null, $fileNameAsPrefix = true)
	{
		if ($defaults != null) {
			if ($defaults instanceof Properties) {
				$this->data = $this->data + $defaults->toArray();
			}
			else if (is_array($defaults)) {
				$this->data = $this->data + $defaults;
			}
			else {
				throw new InvalidArgumentException("\$defaults needs to be an instance of Properties or an array.");
			}
		}
		$this->fileNameAsPrefix = $fileNameAsPrefix;
	}
	
	/**
	 * Load properties from a file.
	 * If $file has 'ini' extension, this method uses function <code>parse_ini_file()</code> internally to parse values including sections.
	 * If $file has 'php' extension, this method looks for the first declared array inside $file.
	 * Values are merged with current data.
	 *
	 * @param string $file Absolute file path.
	 * @return void
	 * @throws IOException if an error occurred while loading properties.
	 */
	protected function _loadFile($file)
	{
		$base = basename($file);
		$fileName = substr($base, 0, strrpos($base, '.'));
		$ext = strtolower(substr($base, strrpos($base, '.')+1));
		
		if ($ext == 'ini') {
			$data = parse_ini_file($file, true);
		}
		else {
			$data = include($file);
			if (!isset($data) || !is_array($data)) {
				import("io.IOException");
				throw new IOException("Unable to load properties from file '$file'. No declared variables found.");
			}
		}
		
		if ($this->fileNameAsPrefix) {
			foreach ($data as $k => $v) {
				$this->data["$fileName.$k"] = $v;
			}
		}
		else {
			$this->data = $this->data + $data;
		}

		$this->_loadedFiles[] = $base;
	}
	
	/**
	 * Load properties from a file or directory.
	 * If $file is a directory, each file inside it will be included.
	 * If $file has 'ini' extension, this method uses function <code>parse_ini_file()</code> internally to parse values including sections.
	 * If $file has 'php' extension, it must return an array with valid keys.
	 * Values are merged with current data.
	 *
	 * @param string $file Absolute file path.
	 * @return void
	 * @throws FileNotFoundException if $file is not a valid file or directory.
	 * @throws IOException if an error occurred while loading properties.
	 */
	public function load($file)
	{
		if (is_dir($file)) {
			$d = new DirectoryIterator($file);
			foreach ($d as $f) {
				if ($d->isDir() || strpos($f, '.') === 0 || !$d->isReadable()) continue;
				$this->_loadFile($file . DIRECTORY_SEPARATOR . $f);
			}
		}
		else {
			if (!file_exists($file) || !is_readable($file)) {
				import('io.FileNotFoundException');
				throw new FileNotFoundException("Unable to load properties from file '$file'. It doesn't exist or is inaccessible.");
			}
			$this->_loadFile($file);
		}
	}
	
	/**
	 * Set value for the specified key. If it doesn't exist it will be created.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	/**
	 * Get value for specified key or null if $key does not exist.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return array_key_exists($key, $this->data) ? $this->data[$key] : null;
	}
	
	/**
	 * Get an array of values that match the specified key prefix.
	 *
	 * @param string $prefix
	 * @return mixed[]
	 */
	public function getByPrefix($prefix)
	{
		$values = array();
		foreach ($this->data as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				$values[ltrim(str_replace($prefix, '', $key), ".")] = $value;
			}
		}
		return $values;
	}
	
	public function getLoadedFiles()
	{
		return $this->_loadedFiles;
	}
	
	/**
	 * Checks if $property exists
	 *
	 * @param string $property
	 * @return boolean
	 */
	public function exists($property)
	{
		return array_key_exists($property, $this->data);
	}
	
	/**
	 * Removes a property.
	 *
	 * @param string $property
	 * @return boolean TRUE if property has been successfully removed
	 */
	public function remove($property)
	{
		if (array_key_exists($property, $this->data)) {
			unset($this->data[$property]);
			return true;
		}
		return false;
	}
	
	/**
	 * Return config data as an array.
	 *
	 * @return mixed[]
	 */
	public function toArray()
	{
		return $this->data;
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
		$this->set($offset, $value);
	}
	
	/**
	 * ArrayAccess::offsetExists()
	 *
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
    {
		return $this->exists($offset);
	}
	
	/**
	 * ArrayAccess::offsetUnset()
	 *
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset)
    {
		$this->remove($offset);
	}
	
	/**
	 * ArrayAccess::offsetGet()
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
}
?>
