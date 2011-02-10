<?php

import("gui.highlight.Highlighter");

/**
 * 	SQL code highlighter
 *
 * @package gui.highlight
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class SQLHighlighter extends Highlighter
{
	protected static $keywords = array(
		'UPDATE','DELETE','INSERT','REPLACE','SELECT','FROM','WHERE','LIKE','GROUP','BY','ORDER','HAVING','LIMIT',
		'CREATE','ALTER','DROP','BEGIN','END','LEFT','RIGHT','INNER','JOIN','STRAIGHT','NATURAL',
		'ON','IN','ANY','ALL','SOME','AS','EACH','ROW','AFTER','BEFORE','SET','DECLARE','DELIMITER','OLD','NEW','NOT',
		'TRIGGER','FUNCTION','PROCEDURE','VIEW','TABLE','DEFAULT','COMMENT','ENGINE','CHARSET','PRIMARY','KEY',
		'FOR','IF','SWITCH','THEN','WHILE','CASE','OR','AND','XOR','NULL','UNIQUE','UNSIGNED',
		'CONSTRAINT','FOREIGN','REFERENCES','CASCADE','COLLATE','AUTO_INCREMENT','ROW_FORMAT'
	);
	protected static $functions = array(
		'COUNT','MAX','MIN','AVG','SUM','DATE_FORMAT','TRUNCATE','ROUND','UNIX_TIMESTAMP','CONCAT'
	);
	protected static $dataTypes = array(
		'TINYINT','SMALLINT','INT','MEDIUMINT','BIGINT','NUMERIC','REAL','FLOAT','DECIMAL',
		'TINYTEXT','TEXT','MEDIUMTEXT','LONGTEXT','TINYBLOB','BLOB','MEDIUMBLOB','LONGBLOB','BOOL','BIT','ENUM',
		'VARCHAR','CHAR','TIMESTAMP','DATE','DATETIME','TIME'
	);

	public function __construct()
	{
		parent::__construct();

		$this->setTag('keyword', '<span style="color:blue;font-weight:bold;">', '</span>');
		$this->setTag('function', '<span style="color:magenta">', '</span>');
		$this->setTag('number', '<span style="color:#95139F">', '</span>');
		$this->setTag('data_type', '<span style="color:#8B0808">', '</span>');
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
			"/((?:#|--).*?)$/m",
			array($this, 'extractLineComments'),
			$output
		);
		// multi line comments
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

		// replace data types
		foreach (self::$dataTypes as $d)
			$output = preg_replace("#\b($d)\b#i", $this->tags['data_type'][0].'$1'.$this->tags['data_type'][1], $output);

		// replace functions
		foreach (self::$functions as $f)
			$output = preg_replace("#\b($f)\b#i", $this->tags['function'][0].'$1'.$this->tags['function'][1], $output);

		$output = $this->importAll($output);

		return $this->buildCode($output);
	}
}
?>
