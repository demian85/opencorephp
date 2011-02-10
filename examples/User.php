<?php

/**
 * Class for user management
 *
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class User
{
	public static $tableName = 'Users';
	public static $fields = array(
		'id'			=> 'user_id',
		'name'			=> 'user_username',
		'email'			=> 'user_email',
		'fname'			=> 'user_fname',
		'lname'			=> 'user_lname',
		'password'		=> 'user_password'
	);

	public $userID;
	public $data = null;

	/**
	 * Check username and password and return user id.
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	static function login($username, $password) {
		$db = DB::getConnection();

		$username = $db->quote($username);
		$password = sha1($password);

		$sql = "SELECT user_id
				FROM Users
				WHERE ".self::$fields['name']." = '$username'
				AND ".self::$fields['password']." = '$password'";
		$result = $db->query($sql);

		if ($result->rowCount() > 0) {
			$data = $result->fetch();
			return $data['user_id'];
		}
		return 0;
	}

	/**
	 * Check if user is logged.
	 *
	 * @return boolean
	 */
	static function isLogged() {
		return isset($_SESSION['user']);
	}

	/**
	 * Get logged user.
	 *
	 * @return User
	 */
	static function getLogged() {
		return self::isLogged() ? new self($_SESSION['user']['user_id']) : null;
	}

	/**
	 * Check if email exists and optionally if it's not from the specified user.
	 *
	 * @param string $email
	 * @param int $user_id
	 * @return boolean
	 */
	static public function emailExists($email, $user_id = 0) {
		$db = DB::getConnection();
		$sql = "SELECT 1 FROM ".self::$tableName."
				WHERE ".self::$fields['email']." = '".$db->quote($email)."'";
		if ($user_id > 0) {
			$sql .= " AND ".self::$fields['id']." != ".intval($user_id);
		}
		return $db->query($sql)->rowCount() > 0;
	}

	/**
	 * Check if username exists and optionally if it's not from the specified user
	 * @param string $username
	 * @param int $user_id
	 * @return boolean
	 */
	static public function usernameExists($username, $user_id = 0) {
		$db = DB::getConnection();
		$sql = "SELECT 1 FROM ".self::$tableName."
				WHERE ".self::$fields['name']." = '".$db->quote($username)."'";
		if ($user_id > 0) {
			$sql .= " AND ".self::$fields['id']." != ".intval($user_id);
		}
		return $db->query($sql)->rowCount() > 0;
	}

	/**
	 * Add a new user and return an instance of this class.
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @param string $fname
	 * @param string $lname
	 * @return User
	 */
	static function add($username, $email, $password, $fname, $lname) {
		$db = DB::getConnection();
		$sql = DB::createInsert(self::$tableName, array(
			self::$fields['name']		=> $username,
			self::$fields['email']		=> $email,
			self::$fields['fname']		=> $fname,
			self::$fields['lname']		=> $lname,
			self::$fields['password']	=> sha1($password)
		));
		$db->exec($sql);
		return new self($db->lastInsertId());
	}




	function __construct($user_id) {
		$this->userID = (int)$user_id;
	}

	function __get($property) {
		if (!$this->data) {
			$this->getData();
		}
		return isset($this->data[$property]) ? $this->data[$property] : null;
	}

	/**
	 * Checks if user exists.
	 *
	 * @param int $user_id
	 * @return boolean
	 */
	function exists() {
		$db = DB::getConnection();
		$sql = "SELECT 1 FROM ".self::$tableName."
				WHERE ".self::$fields['id']." = ".$this->userID;
		return $db->query($sql)->rowCount() > 0;
	}

	function initSession() {
		$_SESSION['user'] = array(
			'user_id'	=> $this->userID
		);
	}

	function logout() {
		unset($_SESSION['uesr']);
		session_unset();
		session_destroy();
	}

	function getData() {
		$db = DB::getConnection();
		$sql = "SELECT * FROM ".self::$tableName."
				WHERE ".self::$fields['id']." = " . $this->userID . "
				LIMIT 1";
		$this->data = $db->query($sql)->fetch();
		return $this->data;
	}
}
?>