<?php

// namespace db\mysql;

import("db.Connection", "db.QueryBuilder", "db.mysql.*");

/**
 * Represents a mysql database connection.
 *
 * @package db.mysql
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class MysqlConnection implements Connection
{
	/**
	 * @var Mysqli
	 */
	protected $conn = null;

	/**
	 * Constructor.
	 *
	 * @throws RuntimeException if mysqli extension could not be loaded.
	 */
	public function __construct()
	{
		if (!extension_loaded("mysqli")) {
			throw new RuntimeException("Mysqli extension could not be found.");
		}
	}

	public function __destruct()
	{
		try {
			$this->close();
		} catch (Exception $ex) { }
	}

	public function connect($host, $user, $pass, $dbname, $port = 3306)
	{
		$this->conn = @mysqli_connect($host, $user, $pass, $dbname, $port);
		if (!$this->conn) {
			throw new SQLException("Cannot connect to host '$host': ".mysqli_connect_error(), mysqli_connect_errno());
		}
	}

	public function selectDb($dbname)
	{
		if (!@$this->conn->select_db($dbname)) {
			throw new SQLException("Unable to select database '$dbname': ".$this->conn->error, $this->conn->errno);
		}
	}

	public function close()
	{
		if (!@$this->conn->close()) {
			throw new SQLException("Unable to close connection: ".$this->conn->error, $this->conn->errno);
		}
	}

	public function query($sql /* [array $values], [$rowsPerPage = 0], [$currentPage = 1] */)
	{
		$args = func_get_args();

		if (func_num_args() > 1 && is_array($args[1])) {
			$sql = QueryBuilder::create($this)->bind($sql, $args[1]);
			$rowsPerPage = isset($args[2]) ? $args[2] : 0;
			$currentPage = isset($args[3]) ? $args[3] : 1;
		}
		else {
			$rowsPerPage = isset($args[1]) ? $args[1] : 0;
			$currentPage = isset($args[2]) ? $args[2] : 1;
		}

		if (!is_int($rowsPerPage) || $rowsPerPage < 0) {
			throw new InvalidArgumentException("Rows per page must be an integer >= 0.");
		}

		if ($rowsPerPage > 0) {
			if (stripos($sql, "SQL_CALC_FOUND_ROWS") === false) {
				$sql = preg_replace("#^SELECT(.*?)#i", "SELECT SQL_CALC_FOUND_ROWS $1", trim($sql));
			}
			preg_match('#LIMIT\s+(\d+)$#i', $sql, $limitMatch);
			if ($currentPage < 1) $currentPage = 1;

			$recStart = $currentPage * $rowsPerPage - $rowsPerPage;

			if ($limitMatch) {
				$nativeLimit = (int)$limitMatch[1];
				$maxLimit = $recStart + $rowsPerPage;
				if ($nativeLimit <= $recStart) $recEnd = 0;
				else if ($nativeLimit <= $maxLimit) $recEnd = min($rowsPerPage, $nativeLimit - $recStart);
				else $recEnd = $rowsPerPage;
				$limit = "$recStart, $recEnd";
				$sql = preg_replace('#LIMIT\s+(\d+)$#i', "LIMIT $limit", $sql);
			}
			else {
				$recEnd = $rowsPerPage;
				$sql .= " LIMIT $recStart, $recEnd";
			}
		}

		$result = @$this->conn->query($sql);
		if (!is_object($result)) {
			$error = !$this->conn->errno ? "Provided statement must be a SELECT, SHOW, DESCRIBE or EXPLAIN." : "Error executing query: ".$this->conn->error;
			throw new SQLException($error, $this->conn->errno, $sql);
		}

		if ($rowsPerPage > 0) {
			if ($limitMatch) $fullRowCount = $nativeLimit;
			else $fullRowCount = $this->query("SELECT FOUND_ROWS() as fr")->fetchObject()->fr;
			$pageCount = (int)ceil($fullRowCount / $rowsPerPage);
		}
		else {
			$fullRowCount = -1;
			$pageCount = 0;
		}

		return new MysqlResultSet($result, null, $pageCount, $fullRowCount);
	}

	public function exec($sql, array $values = array())
	{
		if (!empty($values)) {
			$sql = QueryBuilder::create($this)->bind($sql, $values);
		}
		$result = @$this->conn->query($sql);
		if (!$result) {
			throw new SQLException("Error executing query: ".$this->conn->error, $this->conn->errno, $sql);
		}
		return $this->conn->affected_rows;
	}

	public function prepare($sql)
	{
		$stmt = @$this->conn->prepare($sql);
		if (!$stmt) {
			throw new SQLException("Unable to prepare statement: ".$this->conn->error, $this->conn->errno, $sql);
		}
		return new MysqlStatement($stmt);
	}

	public function beginTransaction()
	{
		if (!$this->conn->autocommit(false)) {
			throw new SQLException("Unable to begin transaction: ".$this->conn->error, $this->conn->errno);
		}
	}

	public function commit()
	{
		if (!$this->conn->commit()) {
			throw new SQLException("Cannot commit current transaction: ".$this->conn->error, $this->conn->errno);
		}
	}

	public function rollBack()
	{
		if (!$this->conn->rollback()) {
			throw new SQLException("Cannot roll back current transaction: ".$this->conn->error, $this->conn->errno);
		}
	}

	public function lastInsertId($name = null)
	{
		return (int)$this->conn->insert_id;
	}

	public function setAttribute($attr, $value)
	{
		throw new RuntimeException("Method not implemented yet!");
	}

	public function getAttribute($attr)
	{
		throw new RuntimeException("Method not implemented yet!");
	}

	public function quote($input, $characters = '')
	{
		if (is_array($input)) {
			$value = array_map(array($this, 'quote'), $input);
		}
		else {
			$value = $this->conn->real_escape_string($input);
			for ($i = 0; $i < mb_strlen($characters, Config::getInstance()->get('core.encoding')); $i++) {
				$value = str_replace($characters[$i], "\\{$characters[$i]}", $value);
			}
		}

		return $value;
	}

	public function setCharset($charset)
	{
		if (!$this->conn->set_charset($charset)) {
			throw new SQLException("Cannot set character set: ".$this->conn->error, $this->conn->errno);
		}
	}

	public function listTables()
	{
		$result = $this->query("SHOW TABLES");
		return $result->fetchAll();
	}
}
?>
