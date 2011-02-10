<?php

//namespace db;

/**
 * An exception that provides information on a database access error.
 *
 * @exception
 * @package db
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class SQLException extends Exception
{
	/**
	 * @var integer
	 */
	protected $errorCode;
	/**
	 * @var string
	 */
	protected $sql;

	/**
	 * Constructor
	 *
	 * @param string $msg
	 * @param int $errorCode Database specific error code.
	 * @param string $sql sql statement that caused the exception
	 */
	function __construct($msg = '', $errorCode = 0, $sql = '')
	{
		parent::__construct($msg);
		$this->errorCode = $errorCode;
		$this->sql = preg_replace('#[\t ]+#', ' ', $sql);
	}
	/**
	 * Get database specific error code.
	 *
	 * @return int
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}

	/**
	 * Get the last sql statement that caused the exception
	 *
	 * @return string
	 */
	public function getSQL()
	{
		return $this->sql;
	}

	public function  __toString()
	{
		$str = parent::__toString();
		if ($this->sql) $str .= "\nSQL:\n" . $this->sql;
		return $str;
	}
}
?>
