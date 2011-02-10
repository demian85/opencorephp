<?php

//namespace db;

/**
 * Represents a prepared statement.
 *
 * @package db
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
interface Statement
{
	/**
	 * Integer data type
	 * @var int
	 */
	const PARAM_INT = 1;
	/**
	 * String data type
	 * @var int
	 */
	const PARAM_STR = 2;
	/**
	 * Boolean data type
	 * @var int
	 */
	const PARAM_BOOL = 4;
	/**
	 * NULL data type
	 * @var int
	 */
	const PARAM_NULL = 8;
	/**
	 * Float data type
	 * @var int
	 */
	const PARAM_FLOAT = 16;
	/**
	 * Double data type
	 * @var int
	 */
	const PARAM_DOUBLE = 32;
	/**
	 * Indicates a large object such as blob or text
	 * @var int
	 */
	const PARAM_LOB = 64;
	/**
	 * Indicates an in-out parameter mostly used on stored procedures
	 * @var int
	 */
	const PARAM_IN_OUT = 128;
	
	/**
	 * Binds a value to a corresponding named or question mark placeholder in the SQL statement that was use to prepare the statement.
	 *
	 * @param string|int $param 1-based
	 * @param mixed $value
	 * @param int $type Use class constants PARAM_*
	 * @return void
	 * @throws SQLException
	 */
	public function bindValue($param, $value, $type = 0);
	/**
	 * Binds a PHP variable to a corresponding named or question mark placeholder in the SQL statement that was use to prepare the statement. 
	 *
	 * @param string|int $param 1-based
	 * @param mixed $var
	 * @param int $type Use class constants PARAM_*
	 * @return void
	 * @throws SQLException
	 */
	public function bindParam($param, &$var, $type = 0);
	/**
	 * Execute the prepared statement and return a ResultSet object or number of affected rows.
	 *
	 * @param mixed[] $params An array of values with as many elements as there are bound parameters in the SQL statement being executed.
	 * @return ResultSet|int
	 * @throws SQLException
	 */
	public function execute(array $params = null);
	/**
	 * Closes the cursor. Frees up the connection to the server so that other SQL statements may be issued, but leaves the statement in a state that enables it to be executed again. 
	 *
	 * @return void
	 * @throws SQLException
	 */
	public function closeCursor();
}
?>
