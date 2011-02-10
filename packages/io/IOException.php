<?php

//namespace io;

/**
 * Signals that an I/O exception of some sort has occurred. Eg: when reading or writing a file.
 * 
 * @exception 
 * @package io
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class IOException extends Exception
{
	/**
	 * Constructor.
	 *
	 * @param string $msg
	 */
	function __construct($msg = '')
	{
		parent::__construct($msg);
	}
}
?>
