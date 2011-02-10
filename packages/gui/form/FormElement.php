<?php

//namespace gui;

/**
 * @package gui.form
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class FormElement
{
	protected $label = null;
	protected $value = null;
	protected $unfilteredValue = null;
	protected $attributes = array();
	protected $validator = null;
	protected $filters = array();
	protected $errorMsg = null;
	
	protected function __construct($name, $id = null, $label = null, $validator = null, $filters = array())
	{
		$this->setAttribute('name', $name);
		$this->setAttribute('id', $id);
		$this->label = $label;
		if (is_array($validator)) {
			$this->setValidator($validator['type'], $validator['required'], $validator['errormsg'], $validator['options']);
		}
		foreach ((array)$filters as $filter) {
			$this->addFilter($filter);
		}
	}
	
	protected function _filter($value)
	{
		foreach ($this->filters as $f) {
			switch ($f) {
				case 'lowercase':
					$value = strtolower($value);
					break;
				case 'uppercase':
					$value = strtoupper($value);
					break;
				case 'rtrim':
					$value = rtrim($value);
					break;
				case 'trim':
					$value = trim($value);
					break;
				case 'ltrim':
					$value = ltrim($value);
					break;
				case 'striptags':
					$value = strip_tags($value);
					break;
			}
		}
		
		return $value;
	}
	
	/**
	 * Build element attributes.
	 *
	 * @param mixed[] $attrs
	 * @param boolean $escape Escape html special chars automatically.
	 * @param boolean $booleanToString Boolean atributes will be converted to string.
	 * @return string
	 */
	protected function _buildAttrs(array $attrs, $escape = true, $booleanToString = false)
	{
		$html = '';
		foreach ($attrs as $name => $value) {
			if ($value === null || $value === false) continue;
			if ($value === true) {
				$v = $booleanToString ? "true" : $name;
			}
			else {
				$v = ($escape ? htmlspecialchars($value, ENT_COMPAT) : $value);
			}
			$html .= ' ' . ($name . '="' . $v . '"');
		}
		return $html;
	}
	
	public function setValue($value)
	{
		$this->unfilteredValue = $value;
		$this->value = $this->_filter($value);
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function setLabel($label)
	{
		$this->label = $label;
	}
	
	public function getLabel()
	{
		return $this->label;
	}
	
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}
	
	public function getAttribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}
	
	public function getError()
	{
		return $this->errorMsg;
	}
	
	public function clearError()
	{
		$this->errorMsg = null;
	}
	
	public function isValid($value = null)
	{
		if (!$this->validator || !$this->validator['required']) {
			return true;
		}
		if ($value !== null) {
			$this->setValue($value);
		}
		if (!Input::validate($this->getValue(), $this->validator['type'], $this->validator['options'])) {
			$this->errorMsg = $this->validator['errormsg'];
			return false;
		}
		
		$this->errorMsg = null;
		return true;
	}
	
	/**
	 * Set validator.
	 *
	 * @param string $type
	 * @param boolean $required
	 * @param string $errorMsg
	 * @param mixed[] $options
	 * @return void
	 */
	public function setValidator($type, $required, $errorMsg, array $options = array())
	{
		$this->validator = array(
			'type'		=> $type,
			'required'	=> $required,
			'errormsg'	=> $errorMsg,
			'options'	=> $options
		);
	}
	
	public function addFilter($filter)
	{
		$this->filters[] = $filter;
	}
	
	public function getJSValidator()
	{
		if ($validator) {
			$html .= '<!-- '.self::_buildAttrs($validator, false, true).' -->';
		}
	}
	
	abstract public function getSource();
}

?>
