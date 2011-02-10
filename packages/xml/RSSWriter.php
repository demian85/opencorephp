<?php

//namespace xml;

/**
 * Class for RSS creation.
 * 
 * @package xml
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class RSSWriter
{
	/**
	 * @var string
	 */
	protected $version;
	/**
	 * @var string
	 */
	protected $charset;
	/**
	 * @var XMLWriter
	 */
	protected $xmlWriter;
	/**
	 * @var array
	 */
	protected $channel = array();
	/**
	 * @var array
	 */
	protected $items = array();
	
	/**
	 * Constructor.
	 *
	 * @param string $version
	 * @param string $charset
	 * @throws RuntimeException if XMLWriter class could not be found.
	 */
	function __construct($version = '2.0', $charset = 'UTF-8') {

		if (!class_exists('XMLWriter')) throw new RuntimeException("Class XMLWriter not found!");
		
		$this->version = $version;
		$this->charset = $charset;
		$this->xmlWriter = new XMLWriter();
	}
	
	/**
	 * Set channel.
	 *
	 * @param string $title
	 * @param string $link
	 * @param string $description
	 * @param string $pubDate defaults to current time
	 * @param string $language
	 * @param array $extraElements
	 * @return void
	 */
	public function setChannel($title, $link, $description = '', $pubDate = '', $language = APP_LANGUAGE, array $extraElements = array()) {
		$this->channel['title'] = $title;
		$this->channel['link'] = $link;
		$this->channel['description'] = $description;
		$this->channel['pubDate'] = $pubDate ? $pubDate : date(DATE_RSS);
		$this->channel['language'] = $language;
		if (!empty($extraElements)) {
			$this->channel = array_merge($this->channel, $extraElements);
		}
	}
	
	/**
	 * Set channel image.
	 *
	 * @param string $url
	 * @param string $link
	 * @param string $title
	 * @param string $description
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	public function setImage($url, $link = '', $title = '', $description = '', $width = 0, $height = 0) {
		$this->channel['image'] = array(
			'url'			=> $url,
			'link'			=> $link,
			'title'			=> $title,
			'description'	=> $description,
			'width'			=> (int)$width,
			'height'		=> (int)$height
		);
	}
	
	/**
	 * Add item.
	 *
	 * @param string $title
	 * @param string $link
	 * @param string $description
	 * @param string $pubDate
	 * @param array $extraElements
	 * @return void
	 */
	public function addItem($title, $link, $description = '', $pubDate = '', array $extraElements = array()) {
		$attrs = array(
			'title'			=> $title,
			'link'			=> $link,
			'description'	=> $description,
			'pubDate'		=> $pubDate
		);
		if (!empty($extraElements)) {
			$attrs = array_merge($attrs, $extraElements);
		}
		$this->items[] = $attrs;
	}
	
	/**
	 * Get XML source.
	 *
	 * @return string
	 */
	public function getSource() {
		$xml = $this->xmlWriter;
		$xml->openMemory();
		$xml->setIndentString("\t");
		$xml->setIndent(true);
		$xml->startDocument('1.0', $this->charset);
		$xml->startElement('rss');
		$xml->writeAttribute('version', $this->version);
			$xml->startElement('channel');
				$xml->writeElement('title', $this->channel['title']);
				$xml->writeElement('link', $this->channel['link']);
				if ($this->channel['description']) $xml->writeElement('description', $this->channel['description']);
				$xml->writeElement('language', $this->channel['language']);
				$xml->writeElement('pubDate', $this->channel['pubDate']);
				if (!empty($this->channel['image'])) {
					$xml->startElement('image');
					$xml->writeElement('url', $this->channel['image']['url']);
					$xml->writeElement('link', $this->channel['image']['link']);
					if ($this->channel['image']['title']) $xml->writeElement('title', $this->channel['image']['title']);
					if ($this->channel['image']['description']) $xml->writeElement('title', $this->channel['image']['description']);
					if ($this->channel['image']['width']) $xml->writeElement('title', $this->channel['image']['width']);
					if ($this->channel['image']['height']) $xml->writeElement('title', $this->channel['image']['height']);
					$xml->endElement(); // </image>
				}
				foreach ($this->items as $item)	{
					$xml->startElement('item');
						$xml->writeElement('title', $item['title']);
						$xml->writeElement('link', $item['link']);
						if ($item['description']) $xml->writeElement('description', $item['description']);
						if ($item['pubDate']) $xml->writeElement('pubDate', $item['pubDate']);
					$xml->endElement(); // </item>
				}
			$xml->endElement(); // </channel>
		$xml->endElement(); // </rss>
		return $xml->outputMemory(false);
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
