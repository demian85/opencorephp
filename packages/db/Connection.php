<?php

// namespace db;

import("db.SQLException");

/**
 * Represents a connection with a specific database.
 * SQL statements are executed and results are returned within the context of a connection. 
 *
 * @package db
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
interface Connection
{
	/**
	 * Initiate a database connection.
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $dbname
	 * @param int $port
	 * @throws SQLException
	 */
	public function connect($host, $user, $pass, $dbname, $port = 0);
	/**
	 * Selects the default database to be used when performing queries against the database connection. 
	 *
	 * @param string $dbname
	 * @return void
	 * @throws SQLException
	 */
	public function selectDb($dbname);
	/**
	 * Close database connection.
	 * WARNING: after explicitly closing a connection, you must re-connect manually using the connect() method.
	 *
	 * @return void
	 * @throws SQLException
	 */
	public function close();
	/**
	 * Executes an SQL statement, returning a ResultSet object if statement is a SELECT|SHOW|DESCRIBE.
	 * Optionally, return a paged result set if $rowsPerPage is greater than 0.
	 * The parameters are not fixed, 2nd parameter can be an array or an integer.
	 * If an array if given, those values are used for query binding (see QueryBuilder#bind)
	 * Additionally, the following parameters are accepted:
	 * - int $rowsPerPage Indicates number of rows to show per page. 0 indicates no pagination.
	 * - int $currentPage Indicates the curren page number. It is usually available as an URL parameter.
	 *
	 * @param string $sql
	 * @return ResultSet
	 * @throws SQLException
	 * @see QueryBuilder#bind
	 * @example
	 * $db->query("SELECT * FROM Users WHERE user_id = ? AND user_bdate > ?", array(3, '2010-11-15')) // bind values
	 * $db->query("SELECT * FROM Users WHERE user_id = ? AND user_bdate > ?", array(3, '2010-11-15'), 10) // bind values and return paged query
	 * $db->query("SELECT * FROM Users", 10) // only return paged query
	 * $db->query("SELECT * FROM Users") // simple query
	 */
	public function query($sql /*, [array $values], [$rowsPerPage = 0, $currentPage = 0] */);
	/**
	 * Executes an SQL statement, returning the number of rows affected.
	 *
	 * @param string $sql
	 * @param mized[] $values values used for query binding (see QueryBuilder#bind)
	 * @return int
	 * @throws SQLException
	 * @see QueryBuilder#bind
	 * @example
	 * $db->exec("INSERT INTO Users SET user_name = ?, user_email = ?", array('Rambo', 'rambo@rocks.com'))
	 */
	public function exec($sql, array $values = array());
	/**
	 * Prepares a statement for execution and returns a Statement object.
	 *
	 * @param string $sql
	 * @return Statement
	 * @throws SQLException
	 */
	public function prepare($sql);
	/**
	 * Sets autocommit mode to off and begins transaction.
	 *
	 * @return void
	 * @throws SQLException
	 */
	public function beginTransaction();
	/**
	 * Commits a transaction, returning the database connection to autocommit mode until the next call to <code>beginTransaction()</code>. 
	 *
	 * @return void
	 * @throws SQLException
	 */
	public function commit();
	/**
	 * Rolls back the current transaction, initiated by <code>beginTransaction()</code>.
	 * No action is taken if there isn't any active transaction.
	 *
	 * @return void
	 * @throws SQLException
	 */
	public function rollBack();
	/**
	 * Returns the ID of the last inserted row, or the last value from a sequence object, depending on the underlying driver.
	 * <strong>Note</strong>: This method may not return a meaningful or consistent result across different PDO drivers, because the underlying database may not even support the notion of auto-increment fields or sequences. 
	 *
	 * @param string $name Name of the sequence object from which the ID should be returned. 
	 * @return string|int
	 * @throws SQLException
	 */
	public function lastInsertId($name = null);
	/**
	 * Sets an attribute for this connection.
	 *
	 * @param string|int $attr
	 * @param mixed $value
	 * @return void
	 */
	public function setAttribute($attr, $value);
	/**
	 * Get attribute for this connection.
	 *
	 * @param string|int $attr
	 * @return mixed
	 */
	public function getAttribute($attr);
	/**
	 * Places quotes around the input string (if required) and escapes special characters within the input string, using a quoting style appropriate to the underlying driver.
	 * This method should accept an array of strings as well.
	 *
	 * @param string|string[] $input
	 * @param string $characters additional characters to escape
	 * @return string|string[]
	 * @throws SQLException
	 */
	public function quote($input, $characters = '');
	/**
	 * Sets the default character set to be used when sending data from and to the database server. 
	 *
	 * @param string $charset
	 * @return void
	 * @throws SQLException
	 */
	public function setCharset($charset);
	/**
	 * Get table list from current database
	 *
	 * @return string[]
	 * @throws SQLException
	 */
	public function listTables();
}
?>
