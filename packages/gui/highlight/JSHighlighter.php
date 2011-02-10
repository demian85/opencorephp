<?php

import("gui.highlight.Highlighter");

/**
 * 	Javascript language highlighter
 *
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class JSHighlighter extends Highlighter
{
	protected static $keywords = array(
		'for','if','switch','while','break','continue','do','case','var','in','else','this',
		'new','throw','catch','finally','try','typeof','instanceof',
		'false','true','null','delete','function','const','return'
	);

	public function __construct()
	{
		parent::__construct();

		$this->setTag('keyword', '<span style="color:blue;font-weight:bold;">', '</span>');
		$this->setTag('method', '<span style="color:#9F1409">', '</span>');
		$this->setTag('number', '<span style="color:purple">', '</span>');
	}
	/**
	 * Colorear codigo SQL.
	 *
	 * @param string $input
	 * @return string
	 */
	public function highlight($input)
	{
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
			"#(//.*?)$#m",
			array($this, 'extractLineComments'),
			$output
		);
		// multiline comments
		$output = preg_replace_callback(
			'#/\*(.*?)\*/#is',
			array($this, 'extractComments'),
			$output
		);
		// replace numbers
		$output = preg_replace("#\b(\d+)\b#", $this->tags['number'][0].'$1'.$this->tags['number'][1], $output);

		// replace keywords
		foreach (self::$keywords as $k)
			$output = preg_replace("#\b($k)\b#i", $this->tags['keyword'][0].'$1'.$this->tags['keyword'][1], $output);

		// replace methods
		$output = preg_replace("#(?<=\.)(\w+)(?=\s*\()#i", $this->tags['method'][0].'$1'.$this->tags['method'][1], $output);

		$output = $this->importAll($output);

		return $this->buildCode($output);
	}
}
?>
