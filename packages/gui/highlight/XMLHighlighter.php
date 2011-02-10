<?php

//namespace gui;

import("gui.highlight.Highlighter");

/**
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class XMLHighlighter extends Highlighter
{
	protected static $regs = array(
		'cdata'		=> '#&lt;!\[CDATA\[.*?\]\]&gt;#s',
		'comment'	=> '#&lt;!--.*?--&gt;#is',
		'node'		=> '#&lt;(/?)([\w-]+) ( (?: \s+ [\w-]+ = (["\']).*?\4 )* ) (\s* /?)&gt;#ixs',
	);
	protected $cdataSections = array();
	protected $cdataSectionsCounter = 0;

	public function __construct()
	{
		parent::__construct();

		$this->setTag('tag_name', '<span style="color:purple">', '</span>');
		$this->setTag('attribute_name', '<span style="color:blue">', '</span>');
		$this->setTag('attribute_value', '<span style="color:green">', '</span>');
		$this->setTag('comment', '<span style="color:#aaa">', '</span>');
		$this->setTag('cdata', '<span style="color:#8F115D">', '</span>');
	}

	/**
	 * Extraer comentarios multilinea
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractComments($match)
	{
		$this->comments[$this->commentsCounter] = $this->importCDATA($match[0]);
		$id = "<<mc{$this->commentsCounter}>>";
		$this->commentsCounter++;
		return $id;
	}
	/**
	 * Extraer secciones CDATA
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractCDATA($match)
	{
		$this->cdataSections[$this->cdataSectionsCounter] = $match[0];
		$id = "<<cdata{$this->cdataSectionsCounter}>>";
		$this->cdataSectionsCounter++;
		return $id;
	}
	/**
	 * Importar secciones CDATA y opcionalmente colorear
	 *
	 * @param string $input
	 * @param boolean $parse Colorear o no
	 * @return string
	 */
	protected function importCDATA($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<cdata(\d+)>>#",
			array($this, '_replaceCDATA'),
			$input
		);
	}
	protected function _replaceCDATA($match)
	{
		$start = $this->parse ? $this->tags['cdata'][0] : '';
		$end = $this->parse ? $this->tags['cdata'][1] : '';
		return $start.$this->cdataSections[$match[1]].$end;
	}
	/**
	 * Colorear XML.
	 *
	 * @param string $input
	 * @return string
	 */
	public function highlight($input)
	{
		$output = htmlspecialchars($input, ENT_NOQUOTES);

		$output = preg_replace_callback(
			self::$regs['cdata'],
			array($this, 'extractCDATA'),
			$output
		);

		$output = preg_replace_callback(
			self::$regs['comment'],
			array($this, 'extractComments'),
			$output
		);

		$output = preg_replace_callback(
			self::$regs['node'],
			array($this, '_highlightNodes'),
			$output
		);

		$output = $this->importComments($this->importCDATA($output, true), true);

		return $this->buildCode($output);
	}
	/**
	 * Callback: colorear atributos
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _highlightAtributes($match)
	{
		return $this->tags['attribute_name'][0].$match[1].$this->tags['attribute_name'][1]."=".
				$this->tags['attribute_value'][0].$match[2].$this->tags['attribute_value'][1];
	}
	/**
	 * Colorear atributos
	 *
	 * @param string $input
	 * @return string
	 */
	protected function highlightAtributes($input)
	{
		return preg_replace_callback(
			'#([\w-]+) = ((["\']).*?\3)#ixs',
			array($this, '_highlightAtributes'),
			$input
		);
	}
	/**
	 * Callback: colorear nodos
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _highlightNodes($match)
	{
		$attrs = $this->highlightAtributes($match[3]);
		return '&lt;'.$match[1].$this->tags['tag_name'][0].$match[2].$this->tags['tag_name'][1].$attrs.$match[5].'&gt;';
	}
}
?>
