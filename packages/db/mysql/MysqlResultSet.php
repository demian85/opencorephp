<?php

//namespace db\mysql;

import("db.ResultSet");

/**
 * Represents a mysql database result set.
 *
 * @package db.mysql
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class MysqlResultSet extends ResultSet
{
	/**
	 * @var Mysqli_result
	 */
	protected $result;
	/**
	 * @var Mysqli_stmt
	 */
	protected $stmt;
	/**
	 * @var mixed[]
	 */
	protected $boundCols = array();
	/**
	 * @var string[]
	 */
	protected $_fields = array();
	
	/**
	 * Constructor.
	 *
	 * @param Mysqli_result $result
	 * @param Mysqli_stmt $stmt
	 * @param int $pageCount Number of pages in this result set. 0 indicates no pagination.
	 * @param int $fullRowCount Total number fo rows in this result set. -1 indicates no pagination.
	 */
	function __construct(Mysqli_result $result, Mysqli_stmt $stmt = null, $pageCount = 0, $fullRowCount = -1)
	{
		parent::__construct($pageCount, $fullRowCount);
		$this->result = $result;
		$this->stmt = $stmt;
	}

	protected function _getFields() {
		if (empty($this->_fields)) {
			$i = 0;
			while ($field = $this->result->fetch_field()) {
				$this->_fields[$i++] = $field->name;
			}
		}
		return $this->_fields;
	}
	protected function bindColumns()
	{
		$row = array();
		$fields = $this->_getFields();
		$this->boundCols = array();
		for ($i = 0, $len = count($fields); $i < $len; $i++) {
			$this->boundCols[$i] =& $row[$fields[$i]];
		}
		call_user_func_array(array($this->stmt, "bind_result"), $this->boundCols);
	}
	
	protected function convertFetchMode($mode)
	{
		if (self::FETCH_ASSOC & $mode) {
			$fields = $this->_getFields();
			$i = 0;
			foreach ($this->boundCols as $col) {
				$this->boundCols[$fields[$i]] = $col;
				$i++;
			}
			if ((self::FETCH_NUM & $mode) == 0) {
				for ($i = 0; $i < count($fields); $i++) {
					unset($this->boundCols[$i]);
				}
			}
		}
		else if (self::FETCH_OBJ & $mode) {
			$this->boundCols = (object)$this->boundCols;
		}
	}
	
	public function bindColumn($column, &$var)
	{
		$this->boundCols[$column] =& $var;
		call_user_func_array(array($this->stmt, "bind_result"), $this->boundCols);
	}

	public function fetch($mode = 0)
	{
		if ($mode == 0) $mode = $this->fetchMode;
		$result = null;

		if ($this->stmt) {
			$this->bindColumns();
			$res = $this->stmt->fetch();
			$this->convertFetchMode($mode);
			if ($res) {
				$result = $this->boundCols;
			}
			else if ($res === false) {
				throw new SQLException("Error fetching data: ".$this->stmt->error, $this->stmt->errno);
			}
		}
		else {
			switch ($mode) {
				case self::FETCH_ASSOC:
					$result = $this->result->fetch_assoc();
					break;
				case self::FETCH_NUM:
					$result = $this->result->fetch_row();
					break;
				case self::FETCH_BOTH:
					$result = $this->result->fetch_array();
					break;
				case self::FETCH_OBJ:
					$_res = $this->result->fetch_assoc();
					$result = $_res ? (object)$_res : null;
					break;
				case self::FETCH_BOUND:
					$tmp = $this->result->fetch_array();
					if (is_array($tmp)) {
						foreach ($this->boundCols as $name => &$var) {
							if (array_key_exists($name, $tmp)) {
								$var = $tmp[$name];
							}
						}
						$result = true;
					}
					break;
				default:
					throw new InvalidArgumentException("\$mode is not a valid fetch mode.");
			}
		}
		
		return $result;
	}
	
	public function fetchColumn($index = 0)
	{
		$row = $this->fetch(self::FETCH_NUM);
		if ($row) {
			if (!array_key_exists($index, $row)) {
				throw new IndexOutOfBoundsException("'$index' is not a valid column index.");
			}
			return $row[$index];
		}
		return null;
	}
	
	public function rowCount()
	{
		return $this->result->num_rows;
	}
	
	public function columnCount()
	{
		return $this->result->field_count;
	}
	
	public function seek($rowIndex)
	{
		if (!$this->result->data_seek((int)$rowIndex)) {
			throw new SQLException("Error seeking: ".$this->result->error, $this->result->errno);
		}
	}
	
	public function close()
	{
		$this->result->close();
	}
}
?>
