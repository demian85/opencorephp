<?php

//namespace log;

/**
 * Exception thrown by the Logger class. Indicates any type of log error.
 * If automatic exception logging is enabled, this type of exception will never be logged.
 *
 * @exception 
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class LoggerException extends Exception
{
	public function __construct($message, Exception $cause = null)
	{
		if ($cause) $message .= "\n\nCause: (" . get_class($cause) . ") " . $cause->getMessage();
		parent::__construct($message);
	}
}

?>
