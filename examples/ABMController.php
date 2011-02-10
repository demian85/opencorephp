<?php

class ABMController extends Controller {

	protected $_input;

	protected function _validate() {
		$input = $this->_input;

		// validate form data
		
		return $input->getErrors();
	}

	protected function _getInput() {
		// initialize form data
		$input = new DataInput($_POST);
		$input->init(array(''), 'int');
		$input->init(array(''), 'string', '', 'trim');
		return $input;
	}




	function __construct() {
		parent::__construct();
		$this->_input = $this->_getInput();
	}

	function defaultAction() {
		$view = new AdminView('path/to/view');
		echo $view;
	}

	function deleteAction($id = 0) {
		// validate id

		//delete

		//redirect
		$this->redirect('/');
	}

	function addAction() {
		$errors = array();

		if ($this->request->isPost()) {

			$errors = $this->_validate();

			if (empty($errors)) {
				// add

				// redirect
				$this->redirect('/');
			}
		}

		$view = new AdminView('path/to/view');
		$view->data = $this->_input->getData();
		$view->errors = $errors;
		echo $view;
	}

	function editAction($id = 0) {

		$errors = array();
		$db = DB::getConnection();

		// check if $id exists

		if ($this->request->isPost()) {

			// validate submited form data
			$errors = $this->_validate();

			// in case of an error we need the form data
			$data = $this->_input->getData();

			if (empty($errors)) {

				// edit

				// redirect
				$this->redirect('/');
			}
		}
		else {
			// fetch original form data
			//$data = ;
		}

		$view = new AdminView('path/to/view');
		$view->data = $data;
		$view->errors = $errors;
		echo $view;
	}
}

?>