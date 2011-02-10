<?php

//namespace gui\form;

/**
 * @package gui.form
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
import('gui.HTML', 'util.DataInput');

/**
 * This class represents an HTML form and allows you to add elements that include validation options dynamically.
 * You can render the entire form or any of its elements individually as an HTML fragment, including validation settings.
 * The method isValid() validates the form quickly and smartly using the validator settings of each element.
 * Additionally, this class has useful static methods for creating form elements, which has the following advantages:
 * - HTML special characters will be automatically escaped inside attributes.
 * - Strictly NULL attributes will be ignored.
 * - Boolean attributes will have the same value as its name.
 *
 * @package gui
 * @author OpenCorePHP Team
 */
class Form
{
	/**
	 * Denotes an input of type 'text'
	 * @var string
	 * @static
	 */
	const ELM_TEXTFIELD = 'TextField';
	/**
	 * Denotes a textarea element'
	 * @var string
	 * @static
	 */
	const ELM_TEXTAREA = 'TextArea';
	/**
	 * Denotes an input of type 'checkbox'
	 * @var string
	 * @static
	 */
	const ELM_CHECKBOX = 'CheckBox';
	/**
	 * Denotes an input of type 'radio'
	 * @var string
	 * @static
	 */
	const ELM_RADIO = 'Radio';
	/**
	 * Denotes a select element
	 * @var string
	 * @static
	 */
	const ELM_SELECT = 'Select';
	/**
	 * Denotes an input of type 'submit'
	 * @var string
	 * @static
	 */
	const ELM_SUBMIT = 'Submit';
	/**
	 * Denotes an input of type 'image'
	 * @var string
	 * @static
	 */
	const ELM_IMAGE = 'Image';
	/**
	 * Denotes an input of type 'reset'
	 * @var string
	 * @static
	 */
	const ELM_RESET = 'Reset';
	/**
	 * Denotes an input of type 'button'
	 * @var string
	 * @static
	 */
	const ELM_BUTTON = 'Button';
	/**
	 * Denotes an input of type 'file'
	 * @var string
	 * @static
	 */
	const ELM_FILE = 'File';
	/**
	 * Denotes an input of type 'hidden'
	 * @var string
	 * @static
	 */
	const ELM_HIDDEN = 'Hidden';
	/**
	 * Definition List layout. <dt> elements will contain the element's label and <dd> elements will contain the source.
	 * @var int
	 * @static
	 */
	const LAYOUT_DL = 1;
	/**
	 * Unordered List layout. Each element will be rendered inside a list item.
	 * @var int
	 * @static
	 */
	const LAYOUT_UL = 2;
	/**
	 * Table layout. There will be a row for each element, everu row has 2 columns, the first contains the element's label and the second the element's source.
	 * @var int
	 * @static
	 */
	const LAYOUT_TABLE = 3;
	
	/**
	 * Form action attribute
	 * @var string
	 */
	protected $action;
	/**
	 * Form method attribute
	 * @var string
	 */
	protected $method;
	/**
	 * Form onsubmit attribute
	 * @var string
	 */
	protected $onsubmit;
	/**
	 * Form enctype attribute
	 * @var string
	 */
	protected $enctype;
	/**
	 * Form extra attributes
	 * @var mixed[]
	 */
	protected $attrs;
	/**
	 * Form elements
	 * @var mixed[]
	 */
	protected $elements = array();
	
	/**
	 * Create a form.
	 *
	 * @param string $action
	 * @param string $method
	 * @param string $onsubmit
	 * @param string $enctype
	 * @param string[] $attrs
	 */
	public function __construct($action = '', $method = 'post', $onsubmit = null, $enctype = null, array $attrs = array())
	{
		$this->action = $action;
		$this->method = $method;
		$this->onsubmit = $onsubmit;
		$this->enctype = $enctype;
		$this->attrs = $attrs;
	}
	
	/**
	 * Build element attributes.
	 *
	 * @param mixed[] $attrs
	 * @param boolean $escape Escape html special chars automatically.
	 * @param boolean $booleanToString Boolean atributes will be converted to string.
	 * @return string
	 */
	private static function _buildAttrs(array $attrs, $escape = true, $booleanToString = false)
	{
		$html = '';
		foreach ($attrs as $name => $value) {
			if ($value === null || $value === false) continue;
			if (is_bool($value)) {
				if ($value === true) $v = $booleanToString ? "true" : $name;
				else $v = "false";
			}
			else {
				$v = ($escape ? htmlspecialchars($value, ENT_COMPAT) : $value);
			}
			$html .= ' ' . ($name . '="' . $v . '"');
		}
		return $html;
	}
	
	/**
	 * Extract and return an element attribute.
	 *
	 * @param mixed[] $elm
	 * @param string $attrName
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	private function _getAttr(&$elm, $attrName, $defaultValue = null)
	{
		if (isset($elm['attrs'][$attrName])) {
			$value = $elm['attrs'][$attrName];
			unset($elm['attrs'][$attrName]);
			return $value;
		}
		return $defaultValue;
	}
	
	/**
	 * Add new form element.
	 *
	 * @param string $type Element type, you can use class constants ELM_*
	 * @param string $name Element name
	 * @param string $id Element id
	 * @param array $attrs Additional element attributes. The following are reserved:
	 * <ul>
	 * 		<li>(array) validator : array with validator options:
	 * 			<ul>
	 * 				<li>(string) type : value type. {@see Input#validate}</li>
	 * 				<li>(boolean) required : determines if element is required.</li>
	 * 				<li>(boolean) trim : determines if string values should be trimmed before validation.</li>
	 * 				<li>(string) errormsg : error message that will be shown to the user when element is invalid.</li>
	 * 			</ul>
	 * 		</li>
	 * 		<li>(string) label : text label that will be shown next to the element. This replaces 'value' attribute for button and submit element types.</li>
	 * 		<li>(array) options : array of options for <select> elements</li>
	 * 		<li>(array|mixed) selected : a single value or an array of values that will be selected by default</li>
	 * </ul>
	 * @return void
	 */
	public function addElement($type, $name, $id, array $attrs = array())
	{
		$attrs['validator'] = array_merge(array(
			'required'	=> false,
			'type'		=> 'string',
			'trim'		=> false,
			'errormsg'	=> ''
		), (isset($attrs['validator']) ? $attrs['validator'] : array()));
		$this->elements[$id] = array(
			'type' 	=> $type,
			'name'	=> $name,
			'attrs'	=> $attrs
		);
	}
	
	/**
	 * Get form elements
	 *
	 * @return mixed[]
	 */
	public function getElements()
	{
		return $this->elements;
	}
	
	/**
	 * Get element source as an HTML fragment.
	 *
	 * @param string $id
	 * @return string
	 */
	public function getElement($id)
	{
		if (!isset($this->elements[$id])) return null;
		
		$elm = $this->elements[$id];
		$validator = $this->_getAttr($elm, 'validator');
		$label = $this->_getAttr($elm, 'label', '');
		
		switch ($elm['type']) {
			case self::ELM_TEXTFIELD:
				$html = HTML::textField($elm['name'], $this->_getAttr($elm, 'value', ''), $id,
										$this->_getAttr($elm, 'class'), $elm['attrs']);
				break;
			case self::ELM_TEXTAREA:
				$html = HTML::textArea($elm['name'], $this->_getAttr($elm, 'rows', 5), $id,
										$this->_getAttr($elm, 'cols', 50), $this->_getAttr($elm, 'value', ''),
										$this->_getAttr($elm, 'class'), $elm['attrs']);
				break;
			case self::ELM_CHECKBOX:
				$html = HTML::checkBox($elm['name'], $this->_getAttr($elm, 'value', 1), $id,
										$this->_getAttr($elm, 'class'), $label, 
										$this->_getAttr($elm, 'checked', false), $elm['attrs']);
				break;
			case self::ELM_RADIO:
				$html = HTML::radio($elm['name'], $this->_getAttr($elm, 'value', 1), $id,
										$this->_getAttr($elm, 'class'), $label, 
										$this->_getAttr($elm, 'checked', false), $elm['attrs']);
				break;
			case self::ELM_SELECT:
				$html = self::comboBox($elm['name'], $this->_getAttr($elm, 'options', array()), 
										$this->_getAttr($elm, 'selected'), $id, 
										$this->_getAttr($elm, 'class'), $this->_getAttr($elm, 'multiple'),
										$this->_getAttr($elm, 'size'), $elm['attrs']);
				break;
			case self::ELM_SUBMIT:
				$html = self::submit($elm['name'], $this->_getAttr($elm, 'value', $label), $id, 
										$this->_getAttr($elm, 'class'), $elm['attrs']);
				break;
			case self::ELM_BUTTON:
				$html = self::button($elm['name'], $this->_getAttr($elm, 'value', $label), $id, 
										$this->_getAttr($elm, 'class'), $this->_getAttr($elm, 'onclick'), 
										$elm['attrs']);
				break;
			case self::ELM_FILE:
				$html = '<input type="file"'.self::_buildAttrs($elm['attrs']).' />';
				break;
			case self::ELM_HIDDEN:
				$html = '<input type="hidden"'.self::_buildAttrs($elm['attrs']).' />';
				break;
		}
		
		if ($validator) {
			$html .= '<!-- '.self::_buildAttrs($validator, false, true).' -->';
		}
		
		return $html;
	}
	
	/**
	 * Get element label as an HTML fragment. If element does not exist or its label is empty NULL is returned.
	 *
	 * @param string $id
	 * @return string
	 */
	public function getElementLabel($id)
	{
		if (!isset($this->elements[$id])) return null;
		$elm = $this->elements[$id];
		$label = $this->_getAttr($elm, 'label');
		if ($label) {
			$html = '<label for="'.$id.'">'.$label.'</label>';
		}
		else {
			$html = null;
		}
		return $html;
	}
	
	/**
	 * Validate form according to each element's validator options.
	 *
	 * @param mixed[] $data Array of data which contains the form values.
	 * @param string[] $errors If provided, this array will be filled with error messages from invalid elements.
	 * @return boolean
	 */
	public function isValid(array $data, &$errors = array())
	{
		
	}
	
	/**
	 * Get form HTML source using the specified layout.
	 *
	 * @param int $layout
	 * @return string
	 */
	public function getSource($layout = self::LAYOUT_DL)
	{
		$html = self::openForm($this->action, $this->method, $this->onsubmit, $this->enctype, $this->attrs);
		$elms = $this->getElements();
		if ($layout == null) {
			$html .= "<div>";
			foreach ($elms as $id => $values) {
				if (in_array($values['type'], array(self::ELM_CHECKBOX, self::ELM_RADIO, self::ELM_BUTTON, self::ELM_SUBMIT))) {
					$html .= $this->getElement($id);
				}
				else {
					$html .= $this->getElementLabel($id).$this->getElement($id);
				}
				$html .= ' ';
			}
			$html .= "</div>";
		}
		else {
			switch ($layout) {
				case self::LAYOUT_DL:
					$html .= '<dl>';
					foreach ($elms as $id => $values) {
						$html .= "\n\t";
						if (in_array($values['type'], array(self::ELM_CHECKBOX, self::ELM_RADIO, self::ELM_BUTTON, self::ELM_SUBMIT))) {
							$html .= '<dd>'.$this->getElement($id).'</dd>';
						}
						else {
							$html .= '<dt>'.$this->getElementLabel($id).'</dt>';
							$html .= '<dd>'.$this->getElement($id).'</dd>';
						}
					}
					$html .= "\n</dl>";
					break;
				case self::LAYOUT_UL:
					$html .= "<ul>";
					foreach ($elms as $id => $values) {
						if (in_array($values['type'], array(self::ELM_CHECKBOX, self::ELM_RADIO, self::ELM_BUTTON, self::ELM_SUBMIT))) {
							$html .= '<li>'.$this->getElement($id).'</li>';
						}
						else {
							$html .= '<li>'.$this->getElementLabel($id).$this->getElement($id).'</li>';
						}
					}
					$html .= "</ul>";
					break;
				case self::LAYOUT_TABLE:
					$html .= "<table>";
					foreach ($elms as $id => $values) {
						if (in_array($values['type'], array(self::ELM_CHECKBOX, self::ELM_RADIO, self::ELM_BUTTON, self::ELM_SUBMIT))) {
							$html .= '<tr><th></th>';
							$html .= '<td>'.$this->getElement($id).'</td></tr>';
						}
						else {
							$html .= '<tr><th>'.$this->getElementLabel($id).'</th>';
							$html .= '<td>'.$this->getElement($id).'</td></tr>';
						}
					}
					$html .= "</table>";
					break;
				default:
					throw new InvalidArgumentException("Invalid layout supplied.");
			}
		}
		$html .= '</form>';
		
		return $html;
	}
	/**
	 * Render form.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getSource();
	}
	
	/**
	 * Open <form> element.
	 *
	 * @param string $action
	 * @param string $method
	 * @param string $onsubmit
	 * @param string $enctype
	 * @param string[] $attrs
	 * @return string
	 */
	static function openForm($action = '', $method = 'post', $onsubmit = null, $enctype = null, array $attrs = array())
	{
		$attrs = array(
			'action' 	=> $action,
			'method'	=> $method,
			'onsubmit'	=> $onsubmit,
			'enctype'	=> $enctype
		) + $attrs;
		$html = '<form'.self::_buildAttrs($attrs).'>';
		return $html;
	}
	
	/**
	 * Close <form> element.
	 *
	 * @return string
	 */
	static function closeForm()
	{
		return '</form>';
	}
	
	/**
	 * Creates a combo box using the <select> element.
	 *
	 * @param string $name
	 * @param mixed[] $options Array of options where keys are option values and values are option texts.
	 * @param mixed|mixed[] $selectedValue Value or array of values that will be selected by default.
	 * @param string $id
	 * @param string $className
	 * @param boolean $multiple
	 * @param int $size Indicates the size in case it is multiple.
	 * @param string[] $attrs
	 * @return string
	 */
	static function comboBox($name, array $options, $selectedValue = null, $id = null, 
								$className = null, $multiple = false, $size = null, array $attrs = array())
	{
		$attrs = array(
			'name' 		=> $name,
			'id'		=> $id,
			'class'		=> $className,
			'multiple'	=> $multiple,
			'size'		=> ($multiple && !$size ? 5 : $size)
		) + $attrs;
		$selectedValue = (array)$selectedValue;
		$html = '<select'.self::_buildAttrs($attrs).'>';
		foreach ($options as $k => $v) {
			$selected = in_array($k, $selectedValue) ? ' selected="selected"' : '';
			$html .= '<option value="' . htmlspecialchars($k, ENT_COMPAT) . '"'.$selected.'>' 
						. htmlspecialchars($v, ENT_COMPAT) . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Creates a label that can contain HTML.
	 *
	 * @param string $for
	 * @param string $contents
	 * @param string $id
	 * @param string $className
	 * @param string[] $attrs
	 * @return string
	 */
	static function label($for, $contents = '', $id = null, $className = null, array $attrs = array())
	{
		$attrs = array(
			'id'	=> $id,
			'class'	=> $className,
			'for'	=> $for
		) + $attrs;
		return '<label'.self::_buildAttrs($attrs).'>'.$contents.'</label>';
	}
}
?>
