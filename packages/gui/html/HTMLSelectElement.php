<?php

//namespace gui\html;

import('gui.html.HTMLFormElement');

/**
 * This class renders a form select element.
 *
 * @package gui.html
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class HTMLSelectElement extends HTMLFormElement {

	protected $options = array();
	protected $selectedOption = null;
	protected $keyAsValue = true;

	public function  __construct($name = null, $id = null, array $options = array()) {
		parent::__construct($name);
		$this->setId($id);
		$this->options = $options;
	}

	public function addOption($value, $text = '') {
		$this->options[$value] = $text;
		return $this;
	}

	public function addOptions(array $options) {
		$this->options += $options;
		return $this;
	}

	public function setOptions(array $options) {
		$this->options = $options;
		return $this;
	}

	public function setKeyAsValue() {
		$this->keyAsValue = $keyAsValue;
		return $this;
	}

	public function setValue($value) {
		$this->selectedOption = $selected;
		return $this;
	}

	public function render() {
		return HTML::select($this->getAttr('name'), $this->getAttr('id'), $this->options, $this->selectedOption, $this->keyAsValue, $this->_getAttrs());
	}
}
?>
