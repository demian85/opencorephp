<?php

// namespace db;

import("db.DB");

/**
 * Class for creating sql statements
 *
 * @package db
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class QueryBuilder {

	/**
	 * @var int
	 */
	private $_bindIndex;
	/**
	 * @var mixed[]
	 */
	private $_bindValues = array();
	/**
	 * @var Connection
	 */
	private $_conn;

	private function _bindQueryCallback($match) {
		if (!array_key_exists($this->_bindIndex, $this->_bindValues)) {
			$index = $this->_bindIndex+1;
			throw new SQLException("Invalid number of supplied parameters. Missing value for placeholder #{$index}");
		}
		$value = $this->_php2Sql($this->_bindValues[$this->_bindIndex++]);
		return $value;
	}

	private function _php2Sql($value) {
		if (is_string($value)) return "'" . $this->_conn->quote($value) . "'";
		else if (is_array($value)) return '(' . implode(',', array_map(array($this, '_php2Sql'), $value)) . ')';
		else return var_export($value, true);
	}

	/**
	 * Create a SQL query using the supplied field names and values.
	 *
	 * @param string $type Valid types are: INSERT, REPLACE, UPDATE
	 * @param string $table Table name
	 * @param array $fields Keys are column names
	 * @param string $where
	 * @param string $fieldPrefix prefix for column names
	 * @return string
	 */
	private function _createSql($type, $table, array $fields, $where = '', $fieldPrefix = '') {
		$sql = ($type == 'INSERT' || $type == 'REPLACE') ? "$type INTO $table SET " : "UPDATE $table SET ";
		$parts = array();
		foreach ($fields as $field => $value) {
			$parts[] = $fieldPrefix . $field . " = " . $this->_php2Sql($value);
		}
		$sql .= implode(", ", $parts);
		if ($where) $sql .= " WHERE $where";
		return $sql;
	}

	/**
	 * Create an instance of this class. Shortcut for method chaining.
	 *
	 * @param string|Connection $conn A Connection instance or a connection name. If NULL 'default' connection will be used.
	 * @return QueryBuilder
	 */
	public static function create($conn = null) {
		return new self($conn);
	}

	/**
	 * Constructor.
	 *
	 * @param string|Connection $conn A Connection instance or a connection name. If NULL 'default' connection will be used.
	 * @see DB#getConnection
	 */
	public function  __construct($conn = null) {
		if ($conn instanceof Connection) {
			$this->_conn = $conn;
		}
		else {
			if (!$conn) $this->_conn = DB::getConnection();
			else $this->_conn = DB::getConnection($conn);
		}
	}

	/**
	 * Replace questions marks with the specified values. Order is preserved.
	 * Values are mapped from php to mysql.
	 * Strings are automatically escaped and arrays are converted to a comma separated list between parenthesis.
	 *
	 * @param string $sql
	 * @param array $values
	 * @return string
	 * @throws SQLException
	 */
	public function bind($sql, array $values) {
		$this->_bindIndex = 0;
		$this->_bindValues = $values;
		$sql = preg_replace_callback('#\?#', array($this, '_bindQueryCallback'), $sql, -1);
		return $sql;
	}

	/**
	 * Create an INSERT statement.
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 */
	public function createInsert($table, array $fields, $fieldPrefix = '') {
		return $this->_createSql('INSERT', $table, $fields, '', $fieldPrefix);
	}

	/**
	 * Create a REPLACE statement.
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 */
	public function createReplace($table, array $fields, $fieldPrefix = '') {
		return $this->_createSql('REPLACE', $table, $fields, '', $fieldPrefix);
	}

	/**
	 * Create an UPDATE statement.
	 *
	 * @param string $table Db table
	 * @param array $fields Array where keys are column names.
	 * @param string $where SQL WHERE as string excluding the WHERE keyword.
	 * @param string $fieldPrefix prefix for column names.
	 * @return string
	 */
	public function createUpdate($table, array $fields, $where = '', $fieldPrefix = '') {
		return $this->_createSql('UPDATE', $table, $fields, $where, $fieldPrefix);
	}
}
?>
