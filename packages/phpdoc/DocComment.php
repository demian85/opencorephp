<?php

/**
 * This class represents a doc-comment from a class/method/field/function.
 *
 * @package phpdoc
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class DocComment
{
	protected $comment;
	protected $tags = array();
	
	/**
	 * Constructor
	 *
	 * @param $comment doc-comment
	 * @return void
	 */
	public function __construct($comment) {
		$this->comment = $comment;
		$this->parse();
	}
	/**
	 * Parse comment and fetch useful tag info
	 *
	 * @return void
	 */
	protected function parse() {
		// clean doc comment
		$this->comment = trim(preg_replace(array("#^/\*\*#m", "#\*/$#m", "#^\s*\*[\t ]*#ms"), "", $this->comment));
		
		// get description
		preg_match("#^(?:@desc\s+)?( (?: (?>[^@]+) | (?<![\r\n])@\w+ )* )#ix", $this->comment, $matches);
		if ($matches) {
			$this->tags['desc'] = trim($matches[1]);
		}

		$matches = null;
		
		// get params
		$params = array();
		preg_match_all('#(?<=^|\r|\n|\r\n)@param \s+ ([\w\[\]]+) \s+ (\$\w+) ( (?: (?>[^@]+) | (?<![\r\n])@ )* )#ix', $this->comment, $matches, PREG_SET_ORDER);
		if ($matches) {
			foreach ($matches as $m) {
				$params[] = array('type' => $m[1], 'name' => $m[2], 'desc' => trim($m[3]));
			}
			$this->tags['param'] = $params;
		}
		
		$matches = null;
		
		// get return
		preg_match('#(?<=^|\r|\n|\r\n)@return \s+ ([\w\[\]]+) (?: \s+ ( (?: (?>[^@]+) | (?<![\r\n])@ )* ) )?#ix', $this->comment, $matches);
		if ($matches) {
			$this->tags['return'] = array('type' => $matches[1], 'desc' => trim(@$matches[2]));
		}
		$matches = null;
		
		// get exceptions thrown
		$throws = array();
		preg_match_all('#(?<=^|\r|\n|\r\n)@throws \s+ (\w+) (?: \s+ ( (?: (?>[^@]+) | (?<![\r\n])@ )* ) )?#ix', $this->comment, $matches, PREG_SET_ORDER);
		if ($matches) {
			foreach ($matches as $m) {
				$throws[] = array('type' => $m[1], 'desc' => trim(@$m[2]));
			}
			$this->tags['throws'] = $throws;
		}
		
		// get other tags
		$matches = null;
		preg_match_all('#(?<=^|\r|\n|\r\n)@(package|subpackage|exception|access|author|version|example|deprecated|copyright|see|since|var) (?: \s+ ( (?: (?>[^@]+) | (?<![\r\n])@ )* ) )?#ix', $this->comment, $matches, PREG_SET_ORDER);
		if ($matches) {
			foreach ($matches as $m) {
				$tag = $m[1];
				$value = empty($m[2]) ? 1 : $m[2];
				if (isset($this->tags[$tag]) && !is_array($this->tags[$tag])) {
					$this->tags[$tag] = (array)$this->tags[$tag];
					array_push($this->tags[$tag], $value);
				}
				else {
					$this->tags[$tag] = trim($value);
				}
			}
		}
	}
	/**
	 * Get comment information
	 *
	 * @return mixed[]
	 */
	public function getTags() {
		return $this->tags;
	}
	/**
	 * Get tag information
	 *
	 * @param string $tag Tag name
	 * @return string NULL is tag does not exist
	 */
	public function getTag($tag) {
		return array_key_exists($tag, $this->tags) ? $this->tags[$tag] : null;
	}
	/**
	 * Get clean comment without asteriscs
	 *
	 * @return string
	 */
	public function getCleanComment() {
		return $this->comment;
	}
	/**
	 * Check if doc-comment is empty
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return empty($this->comment);
	}
}
?>
