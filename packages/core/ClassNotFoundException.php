<?php

/**
 * Exception thrown when an application tries to load a class but it cannot be found.
 * 
 * @exception 
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class ClassNotFoundException extends Exception {
	
	function __construct($msg = '')	{
		parent::__construct($msg);
	}
}
?>