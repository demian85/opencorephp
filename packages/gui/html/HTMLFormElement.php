<?php

//namespace gui\html;

import('gui.HTMLElement');

/**
 * Base class for html form elements.
 *
 * @package gui.html
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class HTMLFormElement extends HTMLElement {

	protected function __construct($name = null, $value = '') {
		parent::__construct();
		$this->setName($name);
		$this->setValue($value);
	}

	public function setName($name) {
		$this->setAttr('name', $name);
		return $this;
	}

	public function setValue($value) {
		$this->setAttr('value', $value);
		return $this;
	}
}
?>
