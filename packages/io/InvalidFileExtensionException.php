<?php

//namespace io;

import("io.IOException");

/**
 * Exception thrown when a file extension is invalid.
 * 
 * @exception 
 * @package io
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class InvalidFileExtensionException extends IOException
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
