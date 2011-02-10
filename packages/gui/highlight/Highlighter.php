<?php

//namespace gui\highlight;

/**
 * Abstract highlighter class. You must extend this class to implement specific languages.
 * 
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class Highlighter
{
	protected $strings = array();
	protected $stringsCounter = 0;
	protected $lcomments = array();
	protected $lcommentsCounter = 0;
	protected $comments = array();
	protected $commentsCounter = 0;
	protected $parse;
	protected $tags = array(
		'string'		=> array('<span style="color:#005F0D">', '</span>'),
		'line_comment'	=> array('<span style="color:#CF630C">', '</span>'),
		'comment'		=> array('<span style="color:#CF630C">', '</span>'),
	);
	protected $textColor = "black";
	protected $backgroundColor = "white";
	public $wrapTag = 'pre';
	
	protected function __construct() {}
	
	/**
	 * Factory method. Returns instance for a specific highlighter.
	 * Supported languages(case insensitive): XML, CSS, SQL, JS, PHP, Java, C/C++.
	 *
	 * @param string $language
	 * @return Highlighter
	 * @throws InvalidArgumentException if $language is not supported
	 */
	public static function init($language)
	{
		switch (strtolower($language)) {
			case 'xml':
			case 'xhtml':
				import("gui.highlight.XMLHighlighter");
				return new XMLHighlighter();
			case 'css':
				import("gui.highlight.CSSHighlighter");
				return new CSSHighlighter();
			case 'sql':
				import("gui.highlight.SQLHighlighter");
				return new SQLHighlighter();
			case 'javascript':
			case 'js':
				import("gui.highlight.JSHighlighter");
				return new JSHighlighter();
			case 'php':
				import("gui.highlight.PHPHighlighter");
				return new PHPHighlighter();
			case 'java':
				import("gui.highlight.JavaHighlighter");
				return new JavaHighlighter();
			case 'c':
			case 'c++':
				import("gui.highlight.CHighlighter");
				return new CHighlighter();
			case 'document':
				import("gui.highlight.DocumentHighlighter");
				return new DocumentHighlighter();
			default:
				throw new InvalidArgumentException("'$language' is not a supported language.");
		}
	}
	
	/**
	 * Setear tags para colorear
	 *
	 * @param string $type Identificador de que se va a colorear. Ej: keyword, function, number
	 * @param string $startTag
	 * @param string $endTag
	 * @return void
	 */
	public function setTag($type, $startTag, $endTag)
	{
		$this->tags[$type] = array($startTag, $endTag);
	}
	/**
	 * Establecer color de texto. Todo lo que no es coloreado.
	 *
	 * @param string $color
	 * @return void
	 */
	public function setTextColor($color)
	{
		$this->textColor = $color;
	}
	/**
	 * Establecer color de fondo. Todo lo que no es coloreado.
	 *
	 * @param string $color
	 * @return void
	 */
	public function setBackgroundColor($color)
	{
		$this->backgroundColor = $color;
	}
	/**
	 * Preparar codigo para devolverlo al navegador.
	 *
	 * @param string $input
	 * @return string
	 */
	protected function buildCode($input)
	{
		$sTag = $this->wrapTag ? '<'.$this->wrapTag.' style="color:'.$this->textColor.';background-color:'.$this->backgroundColor.'">' : '';
		$eTag = $this->wrapTag ? '</'.$this->wrapTag.'>' : '';
		return $sTag.$input.$eTag;
	}
	/**
	 * Colorear lenguaje.
	 *
	 * @param string $str
	 * @return string
	 */
	abstract public function highlight($str);
	/**
	 * Extraer strings
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractStrings($match)
	{
		$this->strings[$this->stringsCounter] = $this->importStrings($match[0]);
		$id = "<<s{$this->stringsCounter}>>";
		$this->stringsCounter++;
		return $id;
	}
	/**
	 * Extraer comentarios de linea
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractLineComments($match)
	{
		$this->lcomments[$this->lcommentsCounter] = $this->importStrings($match[0]);
		$id = "<<lc{$this->lcommentsCounter}>>";
		$this->lcommentsCounter++;
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
		$this->comments[$this->commentsCounter] = $this->importLineComments($this->importStrings($match[0]));
		$id = "<<mc{$this->commentsCounter}>>";
		$this->commentsCounter++;
		return $id;
	}
	/**
	 * Importar strings y opcionalmente colorear
	 *
	 * @param string $input
	 * @param boolean $parse Colorear o no
	 * @return string
	 */
	protected function importStrings($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<s(\d+)>>#",
			array($this, '_replaceStrings'),
			$input
		);
	}
	/**
	 * Importar comentarios de linea y opcionalmente colorear
	 *
	 * @param string $input
	 * @param boolean $parse Colorear o no
	 * @return string
	 */
	protected function importLineComments($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<lc(\d+)>>#",
			array($this, '_replaceLineComments'),
			$input
		);
	}
	/**
	 * Importar comentarios multilinea y opcionalmente colorear
	 *
	 * @param string $input
	 * @param boolean $parse Colorear o no
	 * @return string
	 */
	protected function importComments($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<mc(\d+)>>#",
			array($this, '_replaceComments'),
			$input
		);
	}
	/**
	 * Importar todo y colorear
	 *
	 * @param string $input
	 * @return string
	 */
	protected function importAll($input)
	{
		return $this->importComments($this->importLineComments($this->importStrings($input, true), true), true);
	}
	/**
	 * Callback: reemplazar strings
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _replaceStrings($match)
	{
		$start = $this->parse ? $this->tags['string'][0] : '';
		$end = $this->parse ? $this->tags['string'][1] : '';
		return $start.$this->strings[$match[1]].$end;
	}
	/**
	 * Callback: reemplazar comentarios de linea
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _replaceLineComments($match)
	{
		$start = $this->parse ? $this->tags['line_comment'][0] : '';
		$end = $this->parse ? $this->tags['line_comment'][1] : '';
		return $start.$this->lcomments[$match[1]].$end;
	}
	/**
	 * Callback: reemplazar comentarios multilinea
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function _replaceComments($match)
	{
		$start = $this->parse ? $this->tags['comment'][0] : '';
		$end = $this->parse ? $this->tags['comment'][1] : '';
		return $start.$this->comments[$match[1]].$end;
	}
	protected function replaceWhiteSpace($input)
	{
		return nl2br(preg_replace(array("#  #", "#\t#"), array("&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;&nbsp;"), $input));
	}
}
?>
