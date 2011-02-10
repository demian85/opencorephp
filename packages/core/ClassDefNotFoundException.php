<?php

//namespace core;

/**
 * Thrown when $className.php was loaded but no definition of the class could be found.
 *
 * @exception
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class ClassDefNotFoundException extends RuntimeException
{
	public function __construct($message = '')
	{
		parent::__construct($message);
	}
}
?>