<?php

//namespace core;

/**
 * Thrown when an application attempts to use null in a case where an object is required.
 *
 * @exception
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class NullPointerException extends RuntimeException
{
	public function __construct($message)
	{
		parent::__construct($message);
	}
}
?>