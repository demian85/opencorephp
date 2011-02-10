<?php

import("gui.highlight.Highlighter");

/**
 * PHP code highlighter
 *
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class PHPHighlighter extends Highlighter
{
	protected $heredocs = array();
	protected $heredocsCounter = 0;
	protected $linkFunctions;

	protected static $keywords = array(
		'for','if','switch','while','break','continue','do','case','var','in','else','return',
		'new','throw','catch','try',
		'false','true','null','delete','function','const','class','public','protected','private',
		'static','parent','self','extends','implements','final','abstract','interface','array',
		'int','float','double','string','global',
		'echo','print','isset','unset','__FILE__','__LINE__','__FUNCTION__','__METHOD__','__CLASS__'
	);

	public function __construct()
	{
		parent::__construct();

		$this->setTag('keyword', '<span style="color:blue;font-weight:bold;">', '</span>');
		$this->setTag('var', '<span style="color:#2F0303;font-weight:bold">', '</span>');
		$this->setTag('number', '<span style="color:purple">', '</span>');
		$this->setTag('function', '<span style="color:#8B0808">', '</span>');
		$this->setTag('php_tags', '<span style="color:red">', '</span>');
	}
	/**
	 * Extraer HEREDOCS
	 *
	 * @param mixed $match
	 * @return string
	 */
	protected function extractHEREDOC($match)
	{
		$this->heredocs[$this->heredocsCounter] = $this->importComments($this->importLineComments($this->importStrings($match[0])));
		$id = "<<heredoc{$this->heredocsCounter}>>";
		$this->heredocsCounter++;
		return $id;
	}
	/**
	 * Importar HEREDOCS y opcionalmente colorear
	 *
	 * @param string $input
	 * @param boolean $parse Colorear o no
	 * @return string
	 */
	protected function importHEREDOC($input, $parse = false)
	{
		$this->parse = $parse;
		return preg_replace_callback(
			"#<<heredoc(\d+)>>#",
			array($this, '_replaceHEREDOC'),
			$input
		);
	}
	protected function _replaceHEREDOC($match)
	{
		$start = $this->parse ? $this->tags['string'][0] : '';
		$end = $this->parse ? $this->tags['string'][1] : '';
		return $start.$this->heredocs[$match[1]].$end;
	}
	/**
	 * Colorear codigo PHP.
	 *
	 * @param string $input
	 * @param boolean $showLineNumbers Mostrar numeros de linea.
	 * @param boolean $linkFunctions Linkear funciones nativas de php al manual
	 * @return string
	 */
	public function highlight($input, $showLineNumbers = false, $linkFunctions = false)
	{
		$this->linkFunctions = $linkFunctions;

		if (!preg_match("#^\s*<\?#", $input)) $input = "<?php\n".$input;
		if (!preg_match("#\?>$#", $input)) $input .= "\n?>";

		$output = htmlspecialchars($input, ENT_NOQUOTES);

		// strings
		$output = preg_replace_callback(
			array(
				'# " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ix',
				"# ' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)' #ix"
			),
			array($this, 'extractStrings'),
			$output
		);
		// line comments
		$output = preg_replace_callback(
			"#((?://|\#).*?)$#m",
			array($this, 'extractLineComments'),
			$output
		);
		// multiline comments
		$output = preg_replace_callback(
			'#/\*(.*?)\*/#is',
			array($this, 'extractComments'),
			$output
		);
		// heredocs
		$output = preg_replace_callback(
			'#=\s*(&lt;){3}\s*(\w+)$(.*?)^\2;#ism',
			array($this, 'extractHEREDOC'),
			$output
		);
		// replace start / end tags
		$output = preg_replace("#&lt;\?(?:php)?|\?&gt;#", $this->tags['php_tags'][0].'$0'.$this->tags['php_tags'][1], $output);

		// replace numbers
		$output = preg_replace("#\b(\.?\d+)\b#", $this->tags['number'][0].'$1'.$this->tags['number'][1], $output);

		// replace keywords
		foreach (self::$keywords as $k)
			$output = preg_replace("#\b(?<!\\$)($k)\b#i", $this->tags['keyword'][0].'$1'.$this->tags['keyword'][1], $output);

		// replace vars
		$output = $this->replaceVars($output);

		// replace functions
		$output = preg_replace_callback(
			"#(?<!\\$|&gt;|::)\b([a-z_]\w+)(?=\s*\()#i",
			array($this, '_highlightFunctions'),
			$output
		);

		$output = $this->importHEREDOC($this->importAll($output), true);
		if ($showLineNumbers)
		{
			$lineNumbers = implode("\n", range(1, count(preg_split("#\r\n|\r|\n#", $output))));
			return '<table><tr><td><pre>'.$lineNumbers.'</pre></td><td><pre>'.$output.'</pre></td></tr></table>';
		}

		return $this->buildCode($output);
	}
	/**
	 * Callback: reemplazar strings
	 *
	 * @override
	 * @param mixed $match
	 * @return string
	 */
	/*protected function _replaceStrings($match)
	{
		$start = $this->parse ? $this->tags['string'][0] : '';
		$end = $this->parse ? $this->tags['string'][1] : '';
		return $start.$this->replaceVars($this->strings[$match[1]]).$end;
	}*/
	/**
	 * Colorear variables php
	 *
	 * @param string $input
	 * @return string
	 */
	protected function replaceVars($input)
	{
		return preg_replace("#\\$[a-z_]\w+#i", $this->tags['var'][0].'$0'.$this->tags['var'][1], $input);
	}
	/**
	 * Colorear funciones y opcionalmente linkear al manual php
	 *
	 * @param string[] $match
	 * @return string
	 */
	protected function _highlightFunctions($match)
	{
		if ($this->linkFunctions && function_exists($match[1]))
		{
			try {
				$rf = new ReflectionFunction($match[1]);
				if ($rf->isInternal()) $func = '<a href="http://php.net/'.$match[1].'" title="http://php.net/'.$match[1].'" class="external">'.$match[1].'</a>';
				else $func = $match[1];
			} catch (Exception $e) {
				$func = $match[1];
			}
		}
		else $func = $match[1];
		return $this->tags['function'][0].$func.$this->tags['function'][1];
	}
}
?>
