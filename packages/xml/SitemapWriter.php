<?php

//namespace xml;

/**
 * Class for Sitemap creation.
 * 
 * @package xml
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class SitemapWriter {

	protected $urls = array();

	/**
	 * Constructor.
	 *
	 * @throws RuntimeException if XMLWriter class could not be found.
	 */
	public function  __construct() {
		if (!class_exists('XMLWriter')) throw new RuntimeException("Class XMLWriter not found!");
	}

	/**
	 * Add item. Empty/null properties will be omitted.
	 *
	 * @param string $loc url	 
	 * @param string $changefreq possible values: always, hourly, daily, weekly, monthly, yearly, never
	 * @param float $priority between 0 and 1.0
	 * @param string $lastmod date format is yyyy-mm-dd
	 */
	public function addUrl($loc, $changefreq = 'weekly', $priority = null, $lastmod = null) {
		$this->urls[] = array(
			'loc'			=> $loc,
			'lastmod'		=> $lastmod,
			'changefreq'	=> $changefreq,
			'priority'		=> (float)$priority,
		);
	}

	/**
	 * Get XML source.
	 *
	 * @return string
	 */
	public function getSource() {
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		$xml->setIndent(true);
		$xml->setIndentString("\t");
		$xml->startElement('urlset');
		$xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

		foreach ($this->urls as $url) {
			$xml->startElement('url');
				$xml->writeElement('loc', $url['loc']);
				if ($url['lastmod']) $xml->writeElement('lastmod', $url['lastmod']);
				if ($url['changefreq']) $xml->writeElement('changefreq', $url['changefreq']);
				if ($url['priority']) $xml->writeElement('priority', $url['priority']);
			$xml->endElement();
		}

		$xml->endElement();
		$xml->endDocument();
		return $xml->outputMemory(true);
	}
	
	/**
	 * Save XML source into a file. If provided file does not exist, it will be created.
	 *
	 * @param string $xmlFile
	 * @param string $perms If provided, file will be given this (octal) permissions.
	 * @return void
	 * @throws IOException if unable to write file.
	 */
	public function save($xmlFile, $perms = null) {
		$file = @fopen($xmlFile, 'w+');
		if (!is_resource($file)) {
			import('io.IOException');
			throw new IOException("Unable to write file '$xmlFile'.");
		}
		@fwrite($file, $this->getSource());
		@fclose($file);
		if ($perms) @chmod($xmlFile, $perms);
	}

	/**
	 * Return XML source.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getSource();
	}
}

?>
