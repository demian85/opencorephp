<?php

// namespace db;

import("db.QueryBuilder");

/**
 * Class for creating database connections and performing common db tasks.
 *
 * @package db
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
final class DB
{
	/**
	 * @var Connection[]
	 */
	private static $instances = array();
	
	private function __construct() { }
	
	/**
	 * Get connection for specified database name.
	 * Database connection details must be configured in the "db.php" config file.
	 *
	 * @param string $dbName
	 * @return Connection
	 * @throws InvalidArgumentException if specified database name does not exist.
	 * @throws FileNotFoundException if directory where class is searched is invalid.
	 * @throws ClassNotFoundException if unable to load driver's class.
	 * @throws ClassDefNotFoundException if the class definition could not be found.
	 * @throws SQLException if any database error accurs.
	 */
	public static function getConnection($dbName = 'default')
	{
		if (!isset(self::$instances[$dbName])) {
			$config = Config::getInstance();
			if (!$config->exists("db.$dbName")) {
				throw new InvalidArgumentException("'$dbName' is not a valid database name.");
			}
			$data = (array)$config->get("db.$dbName");
			$className = ucfirst($data['driver']) . "Connection";
			Loader::loadClass("db\\{$data['driver']}\\$className", FRAMEWORK_DIR . '/packages');
			self::$instances[$dbName] = new $className();
			self::$instances[$dbName]->connect(
				$data['host'], $data['username'], $data['password'], $data['dbname'], $data['port']
			);
			self::$instances[$dbName]->setCharset($data['charset']);
		}
		return self::$instances[$dbName];	
	}
	
	/**
	 * Parse DSN string and return array with its parts.
	 *
	 * @param string $dsn
	 * @return string[]
	 */
	public static function parseDSN($dsn)
	{
		$matches = array();
		if (preg_match("#^(\\w+)://(.+?):(.*?)@(.+?)(?:/(.+))?$#i", $dsn, $matches)) {
			return array(
				'driver'	=> $matches[1],
				'host'		=> $matches[2],
				'username'	=> $matches[3],
				'password'	=> $matches[4],
				'dbname'	=> isset($matches[5]) ? $matches[5] : ''
			);
		}
		return null;
	}
	
	/**
	 * This method is deprecated. Use QueryBuilder#createInsert instead
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 * @deprecated
	 * @see QueryBuilder#createInsert
	 */
	public static function createInsert($table, array $fields, $fieldPrefix = '')
	{
		return QueryBuilder::create()->createInsert($table, $fields, $fieldPrefix);
	}
	
	/**
	 * This method is deprecated. Use QueryBuilder#createReplace instead
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 * @deprecated
	 * @see QueryBuilder#createReplace
	 */
	public static function createReplace($table, array $fields, $fieldPrefix = '')
	{
		return QueryBuilder::create()->createReplace($table, $fields, $fieldPrefix);
	}
	
	/**
	 * This method is deprecated. Use QueryBuilder#createUpdate instead
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $where SQL WHERE as string excluding the WHERE keyword.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 * @deprecated
	 * @see QueryBuilder#createUpdate
	 */
	public static function createUpdate($table, array $fields, $where = '', $fieldPrefix = '')
	{
		return QueryBuilder::create()->createUpdate($table, $fields, $where, $fieldPrefix);
	}
}
?>
