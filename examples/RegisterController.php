<?php

/**
 * Example of a typical registration controller.
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class RegisterController extends Controller
{
	protected $input = null;

	protected function _getInput() {
		if (!$this->input) {
			$this->input = new DataInput($_POST);
			$this->input->init(array('username', 'email', 'fname', 'lname', 'password', 'password2'), 'string');
		}
		return $this->input;
	}
	protected function _getForm() {
		$view = new DocumentView('user/register/form', _("Register a new account"));
		$view->data = $this->_getInput();
		$view->errors = array();
		return $view;
	}
	protected function _validate() {
		$input = $this->_getInput();
		$input->validate('username', 'string', _('Invalid username'));
		$input->validate('email', 'email', _('Invalid email'));
		$input->validate('fname', 'string', _('Invalid first name'));
		$input->validate('lname', 'string', _('Invalid last name'));
		$input->validate('password', 'string', _('Invalid password'));
		$errors = $input->getErrors();
		if ($input['password'] != $input['password2']) {
			$errors[] = _("Passwords do not match");
		}
		if (User::usernameExists($input['username'])) {
			$errors[] = _("Your username already exists in our database");
		}
		if (User::emailExists($input['email'])) {
			$errors[] = _("Your email already exists in our database");
		}

		return $errors;
	}

	function __construct() {
		parent::__construct();
		$this->defaultAction = $this->request->isPost() ? 'process' : 'form';
	}

	function formAction() {
		echo $this->_getForm();
	}

	function processAction() {
		$errors = $this->_validate();

		if (empty($errors)) {
			// add
			$input = $this->input;
			User::add($input['username'], $input['email'], $input['password'], $input['fname'],	$input['lname']);
			$this->redirect("/");
		}
		else {
			$view = $this->_getForm();
			$view->errors = $errors;
			echo $view;
		}
	}
}
?>