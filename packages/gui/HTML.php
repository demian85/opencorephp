<?php

//namespace gui;

import('net.URL');

/**
 * Helper class that helps you build html elements.
 *
 * @package gui
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class HTML
{
	/**
	 * Pointer to the next static domain.
	 * @var int
	 */
	private static $_staticDomainPointer = 0;

	private function __construct() { }
	
	private static function _list($type, array $items, $id, $class)
	{
		if (empty($items)) return '';
		$html = "<{$type}l";
		if ($id) $html .= ' id="'.$id.'"';
		if ($class) $html .= ' class="'.$class.'"';
		$html .= '>';
		foreach ($items as $i) {
			$html .= "<li>$i</li>";
		}
		$html .= "</{$type}l>";
		return $html;
	}

	/**
	 * Create an instance of HTMLElement using the factory method.
	 * The class HTML<$tagName>Element must exist in package gui.html
	 *
	 * @param string $tagName
	 * @param string $id
	 * @param string $class
	 * @return HTMLElement
	 */
	public static function create($tagName, $id = null, $class = null)
	{
		$className = 'HTML' . ucfirst($tagName) . 'Element';
		import("gui.html.$className");
		return new $className($id, $class);
	}

	/**
	 * Build element attributes and return them as a string.
	 *
	 * @param mixed[] $attrs Keys are attribute names.
	 * @param boolean $escape Escape html special chars automatically.
	 * @param boolean $booleanToString Boolean atributes will be converted to string. Otherwise, the attribute name will be used as the value.
	 * @return string
	 */
	public static function buildAttrs(array $attrs, $escape = true, $booleanToString = false)
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
	 * Create an HTML element. If $content is strictly null, tag will be self closed.
	 *
	 * @param string $tagName
	 * @param mixed[] $attributes
	 * @param string $content
	 * @return string
	 */
	public static function element($tagName, array $attributes = array(), $content = null)
	{
		$html = '<' . $tagName . self::buildAttrs($attributes);
		if ($content === null) $html .= '/>';
		else $html .= '>' . $content . '</' . $tagName . '>';
		return $html;
	}
	
	/**
	 * Create an HTML table
	 * @param array $columns Column names
	 * @param array[] $rows Matrix with table data
	 * @param string $id
	 * @param string $class
	 * @return string
	 */
	public static function table(array $columns, array $rows, $id = null, $class = null)
	{
		$html = '<table' . self::buildAttrs(array(
			'id'	=> $id,
			'class'	=> $class
		)) . '>';
		if (!empty($columns)) {
			$html .= '<thead><tr>';
			foreach ($columns as $col) {
				$html .= '<th>'.$col.'</th>';
			}
			$html .= '</tr></thead>';
		}
		$html .= '<tbody>';
		foreach ($rows as $row) {
			$html .= '<tr>';
			foreach ((array)$row as $cell) {
				$html .= '<td>'.(string)$cell.'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		
		return $html;
	}

	/**
	 * Creates an HTML table from an associative array, using keys as the 1st column and values as the 2nd column.
	 * 
	 * @param array $columns Column names
	 * @param array[] $rows Matrix with table data
	 * @param string $id
	 * @param string $class
	 * @return string
	 */
	public static function tableAssoc(array $columns, array $rows, $id = null, $class = null)
	{
		$values = array();
		foreach ($rows as $key => $value) {
			$values[] = array($key, $value);
		}
		return self::table($columns, $values, $id, $class);
	}

	/**
	 * Create an ordered list
	 * @param array $items
	 * @param string $id
	 * @param string $class
	 * @return string
	 */
	public static function oList(array $items, $id = null, $class = null)
	{
		return self::_list('o', $items, $id, $class);
	}

	/**
	 * Create an unordered list
	 * @param array $items
	 * @param string $id
	 * @param string $class
	 * @return string
	 */
	public static function uList(array $items, $id = null, $class = null)
	{
		return self::_list('u', $items, $id, $class);
	}
	
	/**
	 * Create a navigation bar (unordered list with links).
	 * 
	 * @param array $items Each item is an html anchor, key is the href and value is the link content. 
	 * @param string $id
	 * @param string $class
	 * @return string
	 */
	public static function navbar(array $items, $id = null, $class = null)
	{
		if (empty($items)) return "";
		$html = '<ul';
		if ($id) $html .= ' id="'.$id.'"';
		if ($class) $html .= ' class="'.$class.'"';
		$html .= '>';
		foreach ($items as $link => $text) {
			$html .= '<li><a href="'.$link.'">'.$text.'</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * Create a select form field.
	 *
	 * @param string $name
	 * @param string $id
	 * @param array $options
	 * @param mixed|mixed[] $selected
	 * @param boolean $keyAsValue
	 * @param array $attrs
	 * @return string
	 */
	public static function select($name, $id, array $options, $selected = null, $keyAsValue = true, array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'		=> $name,
			'id'		=> $id
		), $attrs), false);
		$html = '<select'.$attrs.'>';
		$html .= self::options($options, $keyAsValue, $selected);
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Create options for a select form field.
	 *
	 * @param array $options
	 * @param boolean $keyAsValue Use the array key as the option value
	 * @param mixed|mixed[] $selected A single value or an array of values
	 * @return string
	 */
	public static function options(array $options, $keyAsValue = true, $selected = null)
	{
		$html = '';
		foreach ($options as $value => $text) {
			$_t = htmlspecialchars((string)$text);
			$_v = $keyAsValue ? $value : $_t;
			if (is_array($selected)) {
				$selected = array_map(function($v) {
					return (string)$v;
				}, $selected);
				$_selected = in_array((string)$_v, $selected, true) ? ' selected="selected"' : '';
			}
			else $_selected = ((string)$selected === (string)$_v) ? ' selected="selected"' : '';
			$html .= '<option value="'.$_v.'"'.$_selected.'>'.$_t.'</option>';
		}
		return $html;
	}

	/**
	 * Create an option for a select form field.
	 *
	 * @param string $value
	 * @param string $text
	 * @param boolean $selected
	 * @param boolean $disabled
	 * @return string
	 */
	public static function option($value = '', $text = '', $selected = false, $disabled = false)
	{
		$_selected = $selected ? ' selected="selected"' : '';
		$_disabled = $disabled ? ' disabled="disabled"' : '';
		$html = '<option value="'.htmlspecialchars($value).'"'.$_selected.$_disabled.'>'.htmlspecialchars($text).'</option>';
		return $html;
	}

	/**
	 * Create an input of type 'checkbox'.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param string $label
	 * @param boolean $checked
	 * @param string $class
	 * @param array $attrs
	 * @return string
	 */
	public static function checkbox($name, $id, $value, $label = '', $checked = false, $class = null, array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'		=> $name,
			'id'		=> $id,
			'value'		=> $value,
			'checked'	=> (bool)$checked,
			'class'		=> $class
		), $attrs));
		$html = '<input type="checkbox"'.$attrs.' />';
		if ($label) $html .= ' <label for="'.$id.'">'.$label.'</label>';
		return $html;
	}

	/**
	 * Create an input of type 'radio'.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param string $label
	 * @param boolean $checked
	 * @param string $class
	 * @param array $attrs
	 * @return string
	 */
	public static function radio($name, $id, $value, $label = '', $checked = false, $class = null, array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'		=> $name,
			'id'		=> $id,
			'value'		=> $value,
			'checked'	=> (bool)$checked,
			'class'		=> $class
		), $attrs));
		$html = '<input type="radio"'.$attrs.' /> <label for="'.$id.'">'.$label.'</label>';
		return $html;
	}

	/**
	 * Create a text form field.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param string $class
	 * @param int $size
	 * @param int $maxlength
	 * @param array $attrs
	 * @return string
	 */
	public static function textField($name, $id = null, $value = '', $class = null, $size = 25, $maxlength = null, array $attrs = array())
	{
		$attrs = array_merge(array(
			'type'		=> 'text',
			'name'		=> $name,
			'id'		=> $id,
			'value'		=> $value,
			'size'		=> $size,
			'class'		=> $class,
			'maxlength'	=> $maxlength
		), $attrs);
		$html = self::element('input', $attrs);
		return $html;
	}

	/**
	 * Create a text form field.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $class
	 * @param int $size
	 * @param array $attrs
	 * @return string
	 */
	public static function passwordField($name, $id = null, $class = null, $size = 25, array $attrs = array())
	{
		$attrs = array_merge(array(
			'type'		=> 'password',
			'name'		=> $name,
			'id'		=> $id,
			'size'		=> $size,
			'class'		=> $class
		), $attrs);
		$html = self::element('input', $attrs);
		return $html;
	}

	/**
	 * Create a button that can contain html.
	 * Uses the <button> tag.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param string $class
	 * @param array $attrs
	 * @return string
	 */
	public static function button($name, $id = null, $value = '', $class = null, array $attrs = array())
	{
		$attrs = array_merge(array(
			'name'		=> $name,
			'id'		=> $id,
			'class'		=> $class
		), $attrs);
		$html = self::element('button', $attrs, $value);
		return $html;
	}

	/**
	 * Create a textarea form field.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param string $class
	 * @param int $rows
	 * @param int $cols
	 * @param array $attrs
	 * @return string
	 */
	public static function textArea($name, $id = null, $value = '', $class = null, $rows = 5, $cols = 30, array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'	=> $name,
			'id'	=> $id,
			'rows'	=> (int)$rows,
			'cols'	=> (int)$cols,
			'class'	=> $class
		), $attrs));
		$html = '<textarea'.$attrs.'>'.$value.'</textarea>';
		return $html;
	}

	/**
	 * Create an input of type 'file'.
	 *
	 * @param string $name
	 * @param string $id
	 * @param string $class
	 * @param boolean $multiple
	 * @param array $attrs
	 * @return string
	 */
	public static function file($name, $id = null, $class = null, $multiple = false, array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'		=> $name,
			'id'		=> $id,
			'multiple'	=> (bool)$multiple,
			'class'		=> $class
		), $attrs));
		$html = '<input type="file"'.$attrs.' />';
		return $html;
	}
	
	/**
	 * Create an input of type 'hidden'.
	 * 
	 * @param string $name
	 * @param string $id
	 * @param string $value
	 * @param array $attrs
	 * @return string
	 */
	public static function hidden($name, $id = null, $value = '', array $attrs = array())
	{
		$attrs = self::buildAttrs(array_merge(array(
			'name'		=> $name,
			'id'		=> $id,
			'value'		=> $value
		), $attrs));
		$html = '<input type="hidden"'.$attrs.' />';
		return $html;
	}
	
	/**
	 * Create a script element.
	 * 
	 * @param string $src
	 * @param string $type
	 * @return string
	 */
	public static function script($src, $type = 'text/javascript')
	{
		return self::element('script', array('src' => $src, 'type' => $type), '');
	}

	/**
	 * Create a link element.
	 *
	 * @param string $href
	 * @param string $type
	 * @param string $rel
	 * @return string
	 */
	public static function link($href, $type = 'text/css', $rel = 'stylesheet')
	{
		return self::element('link', array('type' => $type, 'rel' => $rel, 'href' => $href));
	}

	/**
	 * Create an image.
	 *
	 * @param string $src
	 * @param string $alt
	 * @param string $title
	 * @return string
	 */
	public static function img($src, $alt = '', $title = null)
	{
		return self::element('img', array('src' => $src, 'alt' => $alt, 'title' => $title));
	}

	/**
	 * Get next static domain.
	 * This method alternates domains from {views.static_loader.domains}.
	 *
	 * @return string
	 * @see #src
	 */
	public static function getStaticDomain()
	{
		$config = Config::getInstance();
		$domains = $config->get('views.static_loader.domains');
		$count = count($domains);
		if ($count == 0) return null;
		$domain = str_replace(
					array('{%PROTOCOL}', '{%DOMAIN}'),
					array(Request::getInstance()->getProtocol(), $config['core.domain']),
					$domains[self::$_staticDomainPointer]
			);
		self::$_staticDomainPointer++;
		if (self::$_staticDomainPointer == $count) self::$_staticDomainPointer = 0;
		return $domain;
	}

	/**
	 * Create absolute URL for the specified relative resource using a static domain obtained from method #getStaticDomain.
	 * If no static domain is found or URL does not begin with a slash, the same URL es returned.
	 * 
	 * @param string $src
	 * @return string
	 * @see #getStaticDomain
	 */
	public static function src($src)
	{
		if (strpos($src, '/') !== 0) return $src;
		$domain = self::getStaticDomain();
		$url = $domain ? $domain . $src : $src;
		return $url;
	}
}
?>
