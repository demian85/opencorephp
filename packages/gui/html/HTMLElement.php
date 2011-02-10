<?php

//namespace gui\html;

import('gui.HTML');

/**
 * Base class for html elements.
 *
 * @package gui.html
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class HTMLElement {

	protected $_attributes = array();
	protected $_style = array();
	protected $_childNodes = array();

	/**
	 * Get style attribute.
	 * @return string
	 */
	protected function _getStyles() {
		$style = array();
		foreach ($this->_style as $prop => $value) $style[] = "$prop:$value";
		return implode(';', $style);
	}

	protected function _getAttrs() {
		return array_merge($this->_attributes, array('style' => $this->_getStyles()));
	}

	/**
	 * Constructor.
	 * @param string $id
	 * @param string $class
	 */
	protected function __construct($id = null, $class = null) {
		$this->setAttr('id', $id);
		$this->setAttr('class', $class);
	}

	/**
	 * Set attribute.
	 * @param string $name
	 * @param string $value
	 * @return HTMLElement
	 */
	public function setAttr($name, $value) {
		$this->_attributes[$name] = $value;
		return $this;
	}

	/**
	 * Remove attribute.
	 * @param string $name
	 * @return HTMLElement
	 */
	public function removeAttr($name) {
		unset($this->_attributes[$name]);
		return $this;
	}

	public function getAttr($name) {
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}

	/**
	 * Set style property. Styles should replace the style attribute when rendering element. See #_getStyles
	 * @param string $property
	 * @param string $value
	 * @return HTMLElement
	 * @see #_getStyles
	 */
	public function setStyle($property, $value) {
		$this->_style[$property] = $value;
		return $this;
	}

	/**
	 * Set id attribute
	 * @param string $id
	 * @return HTMLElement
	 */
	public function setId($id) {
		$this->setAttr('id', $id);
		return $this;
	}

	/**
	 * Set class attribute.
	 * @param string $class
	 * @return HTMLElement
	 */
	public function setClass($class) {
		$this->setAttr('class', $class);
		return $this;
	}

	/**
	 * Add child element.
	 * @param HTMLElement $node
	 * @return HTMLElement
	 */
	public function add(HTMLElement $node) {
		if ($this->_childNodes === null) $this->_childNodes = array();
		$this->_childNodes[] = $node;
		return $this;
	}

	/**
	 * Get inner html
	 * @return string
	 */
	public function getInnerHTML() {
		$content = '';
		foreach ($this->_childNodes as $node) {
			$content .= $node->render();
		}
		return $content;
	}

	/**
	 * Render element. This method should return the html element source.
	 * @return string
	 */
	abstract public function render();

	public function  __toString() {
		return $this->render();
	}
}
?>
