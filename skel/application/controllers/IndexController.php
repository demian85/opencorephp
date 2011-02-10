<?php

class IndexController extends Controller {

	function __construct() {
		parent::__construct();
	}

	public function defaultAction() {
		$view = new DocumentView('home', "Home");
		$view->setDescription("");
		$view->setKeywords("");
		$view->addJS('@global');
		$view->addCSS('@global');
		echo $view;
	}
}
?>