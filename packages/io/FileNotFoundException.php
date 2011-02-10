<?php

//namespace io;

import("io.IOException");

/**
 * Signals that an attempt to open the file denoted by a specified pathname has failed.
 * This exception will be thrown when a file with the specified pathname does not exist or for some reason is inaccessible. 
 * 
 * @exception 
 * @package io
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class FileNotFoundException extends IOException
{
	/**
	 * Constructor
	 *
	 * @param string $msg
	 */
	function __construct($msg = '')
	{
		parent::__construct($msg);
	}
}
?>
