<?php

//namespace util;

/**
 * Class for interchanging messages between pages.
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Messenger {
	/**
	 * @var Messenger
	 * @static 
	 */
	protected static $instance = null;
	/**
	 * Session key
	 * @var string
	 */
	protected $sessionKey;
	/**
	 * Reference to $_SESSION global array
	 * @var array
	 */
	protected $session;
	
	/**
	 * Constructor.
	 *
	 * @param string $sessionKey Key that will be added to the $_SESSION global array
	 */
	protected function __construct($sessionKey = '__messenger__')
	{
		$this->sessionKey = (string)$sessionKey;
		if (!isset($_SESSION)) {
			@session_start();
		}
		if (!isset($_SESSION[$this->sessionKey])) {
			$_SESSION[$this->sessionKey] = array();
		}
		$this->session =& $_SESSION[$this->sessionKey];
	}
	
	/**
	 * Get instance of this class using Singleton pattern.
	 *
	 * @return Messenger
	 */
	public static function getInstance()
	{
		if (self::$instance == null) self::$instance = new self();
		return self::$instance;
	}
	
	/**
	 * Add a single message or an array of messages for the specified page id.
	 * If id is NULL, the message will be added for all the previously created entries.
	 *
	 * @param string $id
	 * @param string|string[] $text
	 * @return void
	 */
	public function add($id, $text)
	{
		if ($id === null) {
			foreach ($this->session as &$s) {
				$s = array_merge($s, (array)$text);
			}
		}
		else {
			if (!isset($this->session[$id])) {
				$this->session[$id] = array();
			}
			$this->session[$id] = array_merge($this->session[$id], (array)$text);
		}
	}
	
	/**
	 * Set/replace the contents of the specified page id.
	 *
	 * @param string $id
	 * @param string|string[] $text
	 * @return void
	 */
	public function set($id, $text)
	{
		$this->session[$id] = (array)$text;
	}
	
	/**
	 * Clear messages for the specified page id or all the messages if id is NULL.
	 *
	 * @param string $id
	 * @return void
	 */
	public function clear($id = null)
	{
		if ($id === null) $_SESSION[$this->sessionKey] = array();
		else unset($_SESSION[$this->sessionKey][$id]);
	}
	
	/**
	 * Get list of messages for the specified page id.
	 *
	 * @param string $id
	 * @param boolean $clear If TRUE, the message will be cleared from the session array
	 * @return array
	 */
	public function getList($id, $clear = true)
	{
		if (isset($this->session[$id])) {
			$msg = (array)$this->session[$id];
			if ($clear) $this->clear($id);
		}
		else {
			$msg = array();
		}
		
		return $msg;
	}
	
	/**
	 * Get the first message for the specified page id.
	 *
	 * @param string $id
	 * @param boolean $clear
	 * @return string
	 */
	public function getFirst($id, $clear = true)
	{
		if (!empty($this->session[$id])) {
			$msg = $clear ? array_shift($this->session[$id]) : $this->session[$id][0];
		}
		else {
			$msg = null;
		}
		
		return $msg;
	}
	
	/**
	 * Get the last message for the specified page id.
	 *
	 * @param string $id
	 * @param boolean $clear
	 * @return string
	 */
	public function getLast($id, $clear = true)
	{
		if (!empty($this->session[$id])) {
			$msg = $clear ? array_pop($this->session[$id]) : $this->session[$id][count($this->session[$id])-1];
		}
		else {
			$msg = null;
		}
		
		return $msg;
	}
}

?>
