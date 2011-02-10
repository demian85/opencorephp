<?php

//namespace core;

/**
 * Thrown to indicate that an index of some sort is out of range. 
 * 
 * @exception
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class IndexOutOfBoundsException extends OutOfBoundsException
{
	public function __construct($msg = '')
	{
		parent::__construct($msg);
	}
}
?>