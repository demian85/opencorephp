<?php

// namespace db\mysql;

import("db.Statement");

/**
 * Represents a MySQL prepared statement.
 *
 * @package db.mysql
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class MysqlStatement implements Statement
{
	/**
	 * @var Mysqli_stmt
	 */
	protected $stmt;
	/**
	 * @var mixed[]
	 */
	protected $boundParams = array();
	
	public function __construct(Mysqli_stmt $stmt)
	{
		$this->stmt = $stmt;
	}
	
	/**
	 * Bind parameters. This method should be called only once right before statement execution.
	 *
	 * @return void
	 */
	protected function bindParams()
	{
		if (empty($this->boundParams)) return;
		ksort($this->boundParams);
		$types = "";
		$values = array();
		foreach ($this->boundParams as &$p) {
			$types .= $this->getType($p['type']);
			$values[] =& $p['value'];
		}
		array_unshift($values, $types);
		call_user_func_array(array($this->stmt, "bind_param"), $values);
	}
	
	/**
	 * Get MySQL specific data type identifier. If $type is unknown, "s" is returned.
	 *
	 * @param int $type
	 * @return string
	 */
	protected function getType($type)
	{
		switch ($type) {
			case self::PARAM_INT:
				return "i";
			case self::PARAM_FLOAT:
			case self::PARAM_DOUBLE:
				return "d";
			case self::PARAM_LOB:
				return "b";
			default:
			case self::PARAM_STR:
				return "s";
		}
	}
	
	public function bindValue($param, $value, $type = self::PARAM_STR)
	{
		if ($param < 1) {
			throw new InvalidArgumentException("\$param must be greater than 0.");
		}
		$this->boundParams[$param] = array('value' => $value, 'type' => $type);
	}
	
	public function bindParam($param, &$var, $type = self::PARAM_STR)
	{
		if ($param < 1) {
			throw new InvalidArgumentException("\$param must be greater than 0.");
		}
		$this->boundParams[$param] = array('type' => $type);
		$this->boundParams[$param]['value'] =& $var;
	}
	
	public function execute(array $params = array())
	{
		foreach ($params as $k => $p) {
			$this->bindValue($k, $p);
		}
		$this->bindParams();
		if (!@$this->stmt->execute()) {
			throw new SQLException("Statement execution error: ".$this->stmt->error, $this->stmt->errno);
		}
		//$this->boundParams = array();
		$result = $this->stmt->result_metadata();
		if (!is_object($result)) return $this->stmt->affected_rows;
		else return new MysqlResultSet($result, $this->stmt);
	}
	
	public function closeCursor()
	{
		if (!@$this->stmt->close()) {
			throw new SQLException("Unable to close statement: ".$this->stmt->error, $this->stmt->errno);
		}
	}
}
?>
