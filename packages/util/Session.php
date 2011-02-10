<?php

/**
 * TODO FINISH!!
 * Provee metodos basicos para leer/escribir sesiones en la DB.
 * La estructura de la tabla es la siguiente:
 *
 * CREATE TABLE `Sessions` (
	  `sess_id` int(11) NOT NULL auto_increment,
	  `sess_last_access` datetime NOT NULL,
	  `sess_key` varchar(32) default NULL,
	  `sess_expiry` int(10) unsigned default NULL,
	  `sess_value` longblob,
	  `user_id` int(11) default NULL,
	  `sess_server_id` int(11) default '0',
	  `sess_count_pages` int(11) NOT NULL default '1',
	  PRIMARY KEY  (`sess_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Session
{
	protected $_lifeTime;
	protected $_db;
	protected $_dbTable;

	/**
	 * Constructor
	 *
	 * @param string $dbTable database table name where session data is stored
	 * @param integer $lifeTime session life time in seconds
	 */
	public function __construct($dbTable, $lifeTime = 0)
	{
		$this->_dbTable = $dbTable;
		$this->_db = DB::getConnection();
		$this->_lifeTime = (int)$lifeTime;
	}

	function __destruct()
	{
		session_write_close();
	}

	/**
	 * Open session
	 *
	 * @param string $savePath
	 * @param string $sessName
	 * @return boolean
	 */
	public function open($savePath, $sessName)
	{
		if ($this->_lifeTime == 0) {
			$this->_lifeTime = get_cfg_var("session.gc_maxlifetime");
		}
		return true;
	}

	/**
	 * Close session
	 *
	 * @return boolean
	 */
	public function close()
	{
	   try {
			if ($this->_lifeTime == 0) {
				$this->gc(ini_get('session.gc_maxlifetime'));
			}
			return true;
	   } catch (Exception $e) {}
	}

	/**
	 * Read session
	 *
	 * @param string $key
	 * @return string
	 */
	public function read($key)
	{
		$sql = "DELETE FROM {$this->_dbTable}
				WHERE sess_expiry < '" . time() . "'";

		$this->_db->exec($sql);

		$qid = "SELECT sess_value
				FROM {$this->_dbTable}
				WHERE sess_key = '" .$key. "'
				and sess_expiry > '" . time() . "'";

		$result =  $this->_db->query($qid);
		$data = $result->fetch();

		if (isset($data[0])) return $data[0];

		return "";
	}

	/**
	 * Write session
	 *
	 * @param string $key
	 * @param string $val
	 * @return boolean
	 */
	public function write($key, $val)
	{
		try {
			$expiry = time() + $this->_lifeTime;
			$value = $this->_db->escapeString($val);


			$qid = "SELECT count(*) as total
					FROM {$this->_dbTable}
					WHERE sess_key = '".$key."'";

			$total =  $this->_db->executeQuery($qid);

			$user_id=isset($_SESSION['user'])?$_SESSION['user']['id']:0;

			$data=$total->fetchArray();

			$server_id=1;
			$ip=$_SERVER['REMOTE_ADDR'];
			$ses_agent=$_SERVER['HTTP_USER_AGENT'];
			$ses_agent=$this->_db->escapeString($ses_agent);
			$sess_url=$this->_db->escapeString($_SERVER['REQUEST_URI']);

			$vaupdate=" ,sess_url='$sess_url' ";

			if ($data['total'] > 0) {
			  $sql = "UPDATE {$this->_dbTable}
					  SET sess_expiry = '" .$expiry. "', sess_value = '".$value."'
					  ,user_id='".(int)$user_id."'
					  ,sess_last_access=now()
					  $vaupdate
					  ,sess_count_pages =sess_count_pages+1
					  WHERE sess_key = '".$key."'";

			  $this->_db->executeUpdate($sql);
			  return true;

			}
			else{
			  $sql = "INSERT INTO {$this->_dbTable}
						   (sess_key,sess_expiry,sess_value,sess_last_access,sess_server_id,sess_ip,sess_agent,sess_url)
					  VALUES ('".$key."', '".$expiry."', '" .$value."',now(),'$server_id','$ip','$ses_agent','$sess_url')";
			  $this->_db->executeUpdate($sql);
			  return true;
			}
		} catch (Exception $e) { return true; }
	}

	/**
	 * Destroy session
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function destroy($key)
	{
		$sql = "DELETE FROM {$this->_dbTable}
				WHERE sess_key = '" .$key. "'";
		$this->_db->executeUpdate($sql);
		return true;
	}

	/**
	 * Delete session
	 *
	 * @param integer $maxlifetime
	 * @return boolean
	 */
	public function gc($maxlifetime)
	{
		$sql = "DELETE FROM {$this->_dbTable}
				WHERE sess_expiry < '" . time() . "'";
		$this->_db->executeUpdate($sql);
		return true;
	}
}
?>
