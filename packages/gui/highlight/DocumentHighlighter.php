<?php

import(	"gui.highlight.Highlighter",
		"gui.highlight.JSHighlighter",
		"gui.highlight.CSSHighlighter",
		"gui.highlight.XMLHighlighter"
);

/**
 * 	Coloreador de documentos XHTML, incluye CSS y JS
 *
 * @version 0.1a
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class DocumentHighlighter extends XMLHighlighter
{
	protected $scripts = array();
	protected $scriptsCounter = 0;
	protected $styles = array();
	protected $stylesCounter = 0;

	public function __construct()
	{
		parent::__construct();
	}
	/**
	 * Extraer bloques <script>
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractScripts($match)
	{
		$this->scripts[$this->scriptsCounter] = $match[2];
		$id = $match[1]."<<script{$this->scriptsCounter}>>".$match[3];
		$this->scriptsCounter++;
		return $id;
	}
	/**
	 * Extraer bloques <style>
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractStyles($match)
	{
		$this->styles[$this->stylesCounter] = $this->importScripts($match[2]);
		$id = $match[1]."<<style{$this->stylesCounter}>>".$match[3];
		$this->stylesCounter++;
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
		$this->cdataSections[$this->cdataSectionsCounter] = $this->importStyles($this->importScripts($match[0]));
		$id = "<<cdata{$this->cdataSectionsCounter}>>";
		$this->cdataSectionsCounter++;
		return $id;
	}
	/**
	 * Extraer comentarios multilinea
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractComments($match)
	{
		$this->comments[$this->commentsCounter] = $this->importCDATA($this->importStyles($this->importScripts($match[0])));
		$id = "<<mc{$this->commentsCounter}>>";
		$this->commentsCounter++;
		return $id;
	}
	/**
	 * Importar estilos y scripts.
	 *
	 * @param string $input
	 * @return string
	 */
	protected function importAll($input)
	{
		return $this->importStyles($this->importScripts($input, true), true);
	}
	/**
	 * Importar bloques <script>
	 *
	 * @param string $input
	 * @param boolean Colorear
	 * @return string
	 */
	protected function importScripts($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<script(\d+)>>#",
			array($this, '_replaceScripts'),
			$input
		);
	}
	/**
	 * Importar bloques <style>
	 *
	 * @param string $input
	 * @param boolean Colorear
	 * @return string
	 */
	protected function importStyles($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<style(\d+)>>#",
			array($this, '_replaceStyles'),
			$input
		);
	}
	/**
	 * Callback: reemplazar bloques <script>
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _replaceScripts($match)
	{
		$hg = parent::init('js');
		$hg->wrapTag = '';
		if ($this->parse) return $hg->highlight(htmlspecialchars_decode($this->scripts[$match[1]], ENT_NOQUOTES));
		else return $this->scripts[$match[1]];
	}
	/**
	 * Callback: reemplazar bloques <style>
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _replaceStyles($match)
	{
		$hg = parent::init('css');
		$hg->wrapTag = '';
		if ($this->parse) return $hg->highlight(htmlspecialchars_decode($this->styles[$match[1]], ENT_NOQUOTES));
		else return $this->scripts[$match[1]];
	}
	/**
	 * Colorear documento.
	 *
	 * @param string $input
	 * @return string
	 */
	public function highlight($input)
	{
		$output = htmlspecialchars($input, ENT_NOQUOTES);

		/*$output = preg_replace_callback(
			'#&lt;\?(?:php)?.*?\?&gt;#s',
			array($this, 'extractPHP'),
			$output
		);*/

		$output = preg_replace_callback(
			'#(&lt;script.*?&gt;)(.*?)(&lt;/script\s*&gt;)#is',
			array($this, 'extractScripts'),
			$output
		);

		$output = preg_replace_callback(
			'#(&lt;style.*?&gt;)(.*?)(&lt;/style\s*&gt;)#is',
			array($this, 'extractStyles'),
			$output
		);

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

		$output = $this->importAll($this->importComments($this->importCDATA($output, true), true));

		return $this->buildCode($output);
	}
}
?>
