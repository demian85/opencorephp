<?php

//namespace gui\html;

import('gui.html.HTMLFormElement');

/**
 * This class renders a form input of type 'text'.
 *
 * @package gui.html
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class HTMLTextFieldElement extends HTMLFormElement {

	public function  __construct($name = null, $id = null, $value = '') {
		parent::__construct($name, $value);
		$this->setId($id);
	}

	public function render() {
		return HTML::textfield($this->getAttr('name'), $this->getAttr('id'), $this->getAttr('value'),
								$this->getAttr('class'), $this->getAttr('maxlength'), $this->_getAttrs());
	}
}
?>
