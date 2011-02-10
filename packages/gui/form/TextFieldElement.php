<?php

// namespace gui\form

import('gui.form.FormElement');

/**
 * @package gui.form
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class TextFieldElement extends FormElement
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function getSource()
	{
		$html = '<input type="text" '.$this->_getAttributes().' />';
	}
}

?>
