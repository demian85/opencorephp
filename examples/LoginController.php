<?php

/**
 * Example of a typical login controller
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 *
 */
class LoginController extends Controller {

	private $input = null;

	private function _getInput() {
		if (!$this->input) {
			$this->input = new DataInput($_POST);
			$this->input->init(array('username', 'password'), 'string');
		}
		return $this->input;
	}

	private function _getForm() {
		$view = new DocumentView('user/login', _("Login"));
		$view->data = $this->_getInput();
		$view->errors = array();
		return $view;
	}

	private function _process() {
		$input = $this->_getInput();
		$errors = array();

		// check credentials
		$user_id = User::login($input['username'], $input['password']);

		if (!$user_id) {
			// invalid credentials
			$errors[] = _("Invald username or password");
			$view = $this->_getForm();
			$view->errors = $errors;
			echo $view;
		}
		else {
			// ok, init session and redirect
			$user = new User($user_id);
			$user->initSession();
			$this->redirect("/");
		}
	}




	function __construct() {
		parent::__construct('form');
	}

	function formAction() {
		if ($this->request->isPost()) {
			$this->_process();
		}
		else {
			echo $this->_getForm();
		}
	}
}

?>