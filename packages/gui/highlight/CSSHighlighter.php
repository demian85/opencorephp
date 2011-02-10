<?php

import("gui.highlight.Highlighter");
	
/**
 * 	CSS code highlighter.
 *
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class CSSHighlighter extends Highlighter
{
	public function __construct()
	{
		parent::__construct();

		$this->setTag('selector', '<span style="color:purple">', '</span>');
		$this->setTag('property_name', '<span style="color:blue">', '</span>');
		$this->setTag('property_value', '<span style="color:#6F0707">', '</span>');
		$this->setTag('number', '<span style="color:magenta">', '</span>');
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

		// strings
		$output = preg_replace_callback(
			array(
				'# " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ix',
				"# ' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)' #ix"
			),
			array($this, 'extractStrings'),
			$output
		);
		// multiline comments
		$output = preg_replace_callback(
			'#/\*(.*?)\*/#is',
			array($this, 'extractComments'),
			$output
		);

		$output = preg_replace_callback(
			"#(.+?)\{ (.*?) \}#ixs",
			array($this, '_highlightBlocks'),
			$output
		);

		$output = $this->importComments($this->importStrings($output, true), true);

		return $this->buildCode($output);
	}
	protected function _highlightBlocks($match)
	{
		$output = $this->tags['selector'][0].$match[1].$this->tags['selector'][1];
		$output .= "{".preg_replace_callback(
			"#(?<=^|;)(\s*[\w-]+)(\s*:)#is",
			array($this, '_highlightProperties'),
			$match[2]
		)."}";
		return $output;
	}
	protected function _highlightProperties($match)
	{
		$output = $this->tags['property_name'][0].$match[1].$this->tags['property_name'][1].$match[2];
		//$output .= $match[2].$this->tags['property_value'][0].$match[3].$this->tags['property_value'][1].";";
		return $output;
	}
}
?>
