<?php

//namespace gui;

import('gui.View', 'gui.HTML');

/**
 * This class renders an (X)HTML document. Supports HTML 5.
 * You can set any number of templates to be rendered inside de document body.
 *
 * @package gui
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class DocumentView extends View
{
	/**
	 * @var string
	 * @static
	 */
	const DTD_XHTML_TRANSITIONAL = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	/**
	 * @var string
	 * @static
	 */
	const DTD_XHTML_STRICT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	/**
	 * @var string
	 * @static
	 */
	const DTD_XHTML_FRAMESET = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
	/**
	 * @var string
	 * @static
	 */
	const DOCTYPE_HTML5 = '<!DOCTYPE html>';
	/**
	 * Document syntax: XHTML 1.0
	 * @var int
	 * @static
	 */
	const SYNTAX_XHTML1 = 1;
	/**
	 * Document syntax: HTML 5
	 * @var int
	 * @static
	 */
	const SYNTAX_HTML5 = 2;
	/**
	 * Document syntax: XHTML 5
	 * @var int
	 * @static
	 */
	const SYNTAX_XHTML5 = 3;

	/**
	 * Global header templates.
	 * @var string[]
	 * @static
	 */
	protected static $_headers = null;
	/**
	 * Global footer templates.
	 * @var string[]
	 * @static
	 */
	protected static $_footers = null;
	/**
	 * Global links.
	 * @var string[]
	 * @static
	 */
	protected static $_globalLinks = array();
	/**
	 * Global css files.
	 * @var string[]
	 * @static
	 */
	protected static $_globalCSS = array();
	/**
	 * Global scripts.
	 * @var string[]
	 * @static
	 */
	protected static $_globalJS = array();
	/**
	 * @var string[]
	 */
	protected static $_globalJSVars = array();
	/**
	 * @var string
	 */
	protected static $_globalHeadContent = '';

	/**
	 * @var boolean
	 */
	protected $_showXMLDef = false;
	/**
	 * @var string
	 */
	protected $_xmlVersion = '1.0';
	/**
	 * @var string
	 */
	protected $_docType;
	/**
	 * @var string
	 */
	protected $_syntax;
	/**
	 * @var string
	 */
	protected $_lang;
	/**
	 * @var string
	 */
	protected $_charset;
	/**
	 * @var string
	 */
	protected $_title;
	/**
	 * @var string
	 */
	protected $_keywords = array();
	/**
	 * @var string
	 */
	protected $_description = '';
	/**
	 * @var string
	 */
	protected $_favIcon = '';
	/**
	 * @var string
	 */
	protected $_headExtraContent = '';
	/**
	 * @var string
	 */
	protected $_headProfileAttr = '';
	/**
	 * @var string
	 */
	protected $_bodyOnload = '';
	/**
	 * @var string[]
	 */
	protected $_bodyAttributes = array();
	/**
	 * @var string[]
	 */
	protected $_docElmAttributes = array();
	/**
	 * @var string[]
	 */
	protected $_links = array();
	/**
	 * @var string[]
	 */
	protected $_css = array();
	/**
	 * @var string[]
	 */
	protected $_js = array();
	/**
	 * @var string[]
	 */
	protected $_jsVars = array();
	/**
	 * @var string[]
	 */
	protected $_metaTags = array();
	/**
	 * @var string
	 */
	protected $_textDir = 'ltr';
	/**
	 * @var string[]
	 */
	protected $_domOnLoad = array();

	/**
	 * Resolve path and fetch file contents.
	 * If path begins with a slash, the base directory will be the document root.
	 * If path is invalid or file does not exist, an empty string is returned.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function _getFileSource($path)
	{
		$filePath = realpath($path);

		if (!$filePath) {
			if (strpos($path, '/') === 0) {
				$filePath = $this->_config['core.root'] . $path;
				if (!file_exists($filePath)) {
					$filePath = $this->_request->getURL(false, false) . $path;
				}
			}
			else if (strpos($path, 'http') === 0) {
				$filePath = $path;
			}
		}
		
		$source = file_get_contents($filePath);

		return (string)$source;
	}

	/**
	 * Checks if the specified file name is a static file.
	 *
	 * @param string $fileName
	 * @return boolean
	 */
	protected function _isStatic($fileName)
	{
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);
		return in_array(strtolower($extension), array('css','js','jpg','jpeg','gif','png','ico'));
	}

	/**
	 * Check for resource names in the default css/js directories and build path.
	 *
	 * @param array $files
	 * @param string $type css/js
	 * @return array
	 */
	protected function _checkDefaultDir(array $files, $type) {
		$new = array();		
		foreach ($files as $src => $value) {
			if (strpos($src, '@') === 0) {
				$src = $this->_config["views.default_{$type}_dir"] . '/' . substr($src, 1) . ".{$type}";
			}
			$new[$src] = $value;
		}

		return $new;
	}

	/**
	 * Search for a CSS file that matches the module or controller's name and add it.
	 *
	 * @return void
	 */
	protected function _autoloadCSS()
	{
		$config = Config::getInstance();
		$module = Router::getInstance()->getModule();
		$controller = Router::getInstance()->getController();
		$dir = $config->get('views.default_css_dir');
		if ($module) {
			$moduleFilePath = $dir . DIRECTORY_SEPARATOR . $module . '.css';
			if (file_exists($config->get('core.root') . DIRECTORY_SEPARATOR . ltrim($moduleFilePath, '/'))) {
				$this->addCSS($moduleFilePath);
			}
		}
		$controllerFilePath = $dir . DIRECTORY_SEPARATOR . ($module ? $module . DIRECTORY_SEPARATOR : '') . strtolower($controller->getName()) . '.css';
		if (file_exists($config->get('core.root') . DIRECTORY_SEPARATOR . ltrim($controllerFilePath, '/'))) {
			$this->addCSS($controllerFilePath);
		}
	}

	/**
	 * Search for a JS file that matches the module or controller's name and add it.
	 *
	 * @return void
	 */
	protected function _autoloadJS()
	{
		$config = Config::getInstance();
		$module = Router::getInstance()->getModule();
		$controller = Router::getInstance()->getController();
		$dir = $config->get('views.default_js_dir');
		if ($module) {
			$moduleFilePath = $dir . DIRECTORY_SEPARATOR . $module . '.js';
			if (file_exists($config->get('core.root') . DIRECTORY_SEPARATOR . ltrim($moduleFilePath, '/'))) {
				$this->addJS($moduleFilePath);
			}
		}
		$controllerFilePath = $dir . DIRECTORY_SEPARATOR . ($module ? $module . DIRECTORY_SEPARATOR : '') . strtolower($controller->getName()) . '.js';
		if (file_exists($config->get('core.root') . DIRECTORY_SEPARATOR . ltrim($controllerFilePath, '/'))) {
			$this->addJS($controllerFilePath);
		}
	}

	/**
	 * Get meta tags
	 *
	 * @return string
	 */
	protected function _getMetaData()
	{
		$data = '';
		switch ($this->_syntax) {
			case self::SYNTAX_XHTML1:
				$data .=<<<HTML
<meta http-equiv="Content-Type" content="text/html; charset={$this->_charset}" />
<meta http-equiv="Content-Language" content="{$this->_lang}" />
HTML;
				break;
			case self::SYNTAX_HTML5:
			case self::SYNTAX_XHTML5:
				$data .=<<<HTML
<meta charset="{$this->_charset}" />
HTML;
				break;
		}

		return $data;
	}

	/**
	 * Get URL used to load static files in a single request.
	 *
	 * @param string $type
	 * @param string[] $files
	 * @return string
	 */
	protected function _getStaticLoader($type, array $files)
	{
		return str_replace(
				array('{%PROTOCOL}', '{%DOMAIN}', '{%TYPE}', '{%FILES}'),
				array($this->_request->getProtocol(), $this->_config['core.domain'],
						$type, urlencode(implode(';', $files))),
				$this->_config['views.static_loader.url']
			);
	}

	

	/**
	 * Set the first template that will be included in all views.
	 * Accepts a single template or an array of templates.
	 * NULL removes previously added templates
	 *
	 * @param string|string[] $template
	 * @return void
	 * @throws FileNotFoundException if template doesn't exist.
	 */
	public static function setHeader($template)
	{
		$template = (array)$template;
		foreach ($template as $tpl) {
			self::_checkTemplate($tpl);
		}
		self::$_headers = $template;
	}

	/**
	 * @see #setHeader
	 * @param string|string[] $template
	 * @return void
	 * @throws FileNotFoundException if template doesn't exist.
	 */
	public static function appendHeader($template) {
		$template = (array)$template;
		foreach ($template as $tpl) {
			self::_checkTemplate($tpl);
		}
		self::$_headers = array_merge(self::$_headers, (array)$template);
	}

	/**
	 * Set the last template that will be included in all views.
	 * Accepts a single template or an array of templates.
	 * NULL removes previously added templates
	 *
	 * @param string|string[] $template
	 * @return void
	 * @throws FileNotFoundException if template doesn't exist.
	 */
	public static function setFooter($template)
	{
		$template = (array)$template;
		foreach ($template as $tpl) {
			self::_checkTemplate($tpl);
		}
		self::$_footers = $template;
	}

	/**
	 * @see #setFooter
	 * @param string|string[] $template
	 * @return void
	 * @throws FileNotFoundException if template doesn't exist.
	 */
	public static function appendFooter($template) {
		$template = (array)$template;
		foreach ($template as $tpl) {
			self::_checkTemplate($tpl);
		}
		self::$_footers = array_merge(self::$_footers, (array)$template);
	}
	
	/**
	 * Add global script element(s) that will be included in all views.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param mixed[] $attrs element attributes.
	 * @return void
	 */
	public static function addGlobalScript($src, $attrs = array())
	{
		foreach ((array)$src as $file) {
			self::$_globalJS[$file] = array_merge(array(
				'type'		=> 'text/javascript',
				'inline'	=> false,
				'async'		=> null,
				'defer'		=> null,
				'onload'	=> null
			), $attrs);
		}
	}

	/**
	 * Add global Javascript file(s) that will be included in all views.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param boolean $inline Embed the JS file source inside script tags.
	 * @param string $type
	 * @return void
	 */
	public static function addGlobalJS($src, $inline = false, $type = 'text/javascript')
	{
		foreach ((array)$src as $file) {
			self::$_globalJS[$file] = array(
				'type'		=> $type,
				'inline'	=> $inline,
				'async'		=> null,
				'defer'		=> null,
				'onload'	=> null
			);
		}
	}
	
	/**
	 * Add global asynchronous Javascript file(s) that will be included in all views.
	 * Sets the "async" attribute for the generated script.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param string $onload Javascript code to be executed on script load.
	 * @param boolean $defer Sets the "defer" attribute. Script will be executed after the page has loaded.
	 * @param string $type
	 * @return void
	 */
	public static function addGlobalAsyncJS($src, $onload = null, $defer = false, $type = 'text/javascript')
	{
		foreach ((array)$src as $file) {
			self::$_globalJS[$file] = array(
				'type'		=> $type,
				'inline'	=> false,
				'async'		=> true,
				'defer'		=> $defer,
				'onload'	=> $onload
			);			
		}
	}

	/**
	 * Add global CSS stylesheet(s) that will be included in all views.
	 *
	 * @param string|string[] $href A single file or an array of files.
	 * @param string $title
	 * @param string $id
	 * @param string $media
	 * @return void
	 */
	public static function addGlobalCSS($href, $title = null, $id = null, $media = null)
	{
		$data = array();
		foreach ((array)$href as $link) {
			$data[$link] = array(
				'href'		=> $link,
				'type'		=> 'text/css',
				'rel'		=> 'stylesheet',
				'title'		=> $title,
				'id'		=> $id,
				'media'		=> $media
			);
		}

		self::$_globalCSS = array_merge(self::$_globalCSS, $data);
	}

	/**
	 * Add global <link> tag that will be included in all views.
	 * href and title attributes are automatically escaped.
	 *
	 * @param string $href
	 * @param string $type
	 * @param string $rel
	 * @param string $title
	 * @param string $id
	 * @param string $media
	 * @param string $hreflang
	 * @return void
	 */
	public static function addGlobalLink($href, $type = 'text/css', $rel = 'stylesheet', $title = null,
											$id = null, $media = null, $hreflang = null)
	{
		self::$_globalLinks[] = array(
			'type'		=> $type,
			'rel'		=> $rel,
			'href'		=> $href,
			'title'		=> $title,
			'id'		=> $id,
			'media'		=> $media,
			'hreflang'	=> $hreflang
		);
	}

	/**
	 * Add global JS var that will be included in all views.
	 * Transforms PHP values into JS values using json_encode().
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public static function setGlobalJSVar($name, $value = '')
	{
		self::$_globalJSVars[$name] = json_encode($value);
	}

	/**
	 * Add multiple JS vars that will be included in all views.
	 * Transforms PHP values into JS values using json_encode().
	 * Keys are variable names.
	 *
	 * @param mixed[] $vars
	 * @return void
	 */
	public static function setGlobalJSVars(array $vars)
	{
		foreach ($vars as $name => $value) {
			self::setGlobalJSVar($name, $value);
		}
	}
	
	/**
	 * Add global HTML content available in all documents.
	 * 
	 * @param string $content
	 * @return void
	 */
	public static function addGlobalHeadContent($content)
	{
		self::$_globalHeadContent .= $content;
	}

	/**
	 * Create an instance of DocumentView that renders an error template.
	 * The template name is taken out from {views.tpl_error}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function errorDoc($message, $title = null)
	{
		$config = Config::getInstance();
		if (!$title) $title = l('Error');
		return new static($config->get("views.tpl_error"), $title, array('message' => $message));
	}

	/**
	 * Create an instance of DocumentView that renders a warning template.
	 * The template name is taken out from {views.tpl_warning}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function warningDoc($message, $title = null)
	{
		$config = Config::getInstance();
		if (!$title) $title = l('Warning');
		return new static($config->get("views.tpl_warning"), $title, array('message' => $message));
	}

	/**
	 * Create an instance of DocumentView that renders an info template.
	 * The template name is taken out from {views.tpl_info}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function infoDoc($message, $title = null)
	{
		$config = Config::getInstance();
		if (!$title) $title = l('Information');
		return new static($config->get("views.tpl_info"), $title, array('message' => $message));
	}

	/**
	 * Create an instance of DocumentView that renders a plain template.
	 * The template name is taken out from {views.tpl_plain}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function plainDoc($message, $title = null)
	{
		$config = Config::getInstance();
		return new static($config->get("views.tpl_plain"), $title, array('message' => $message));
	}

	/**
	 * Constructor. Creates an (X)HTML Document.
	 *
	 * @param string|string[] $templates A single template name or an array of templates.
	 * @param string $title Document title. If NULL default title will be used (AVOID!!).
	 * @param mixed[] $data Data array where keys are valid variable names. They will be extracted and converted into local variables for each template.
	 * @throws FileNotFoundException if $template does not represent an existent template file.
	 */
	public function __construct($templates = null, $title = null, array $data = array())
	{
		parent::__construct($templates, $data);

		$config = $this->_config;

		$_doctype = $config->get('views.doctype');
		$this->setDoctype($_doctype ? $_doctype : self::DOCTYPE_HTML5, self::SYNTAX_XHTML5);
		$this->setTitle($title);
		$this->setLang($config->get('app.locale'));
		$this->setCharset($config->get('core.encoding'));
		$this->setFavIcon($config->get('views.favicon'));
		$keywords = $config->get('views.keywords');
		if (!empty($keywords)) {
			$this->setKeywords($keywords);
		}
	}

	/**
	 * Get source for the first part of the document.
	 *
	 * @return string
	 */
	public function beginDocument()
	{
		$config = $this->_config;

		// add global css and js
		$this->prependCSS((array)$config['views.global_css']);
		$this->prependJS((array)$config['views.global_js']);

		// check for autoloading
		if (Router::getInstance()->getController() != null) {
			if ($config->get('views.css_autoload')) {
				$this->_autoloadCSS();
			}
			if ($config->get('views.js_autoload')) {
				$this->_autoloadJS();
			}
		}

		$html = "";
		if ($this->_showXMLDef) $html .=<<<HTML
<?xml version="{$this->_xmlVersion}" encoding="{$this->_charset}" ?>

HTML;
		$docElmAttributes = HTML::buildAttrs($this->_docElmAttributes);
		$_headProfile = $this->_headProfileAttr ? ' profile="'.$this->_headProfileAttr.'"' : '';

		$metaData = $this->_getMetaData();
		$documentTitle = htmlspecialchars($this->_title, ENT_COMPAT);
		$metaKeywords = htmlspecialchars(implode(", ", $this->_keywords), ENT_COMPAT);
		$metaDescription = htmlspecialchars($this->_description, ENT_COMPAT);

		$html .=<<<HTML
{$this->_docType}
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$this->_lang}" lang="{$this->_lang}" dir="{$this->_textDir}"{$docElmAttributes}>
<head{$_headProfile}>
{$metaData}
<title>{$documentTitle}</title>
<meta name="keywords" content="$metaKeywords" />
<meta name="description" content="{$metaDescription}" lang="{$this->_lang}" />

HTML;
		foreach ($this->_metaTags as $attrs) {
			$html .= HTML::element('meta', $attrs) . "\n";
		}

		$cssLinks = $this->_checkDefaultDir(array_merge(self::$_globalCSS, $this->_css), 'css');
		if (!empty($cssLinks) && $this->_config['views.static_loader'] == 1) {
			// single request
			$html .= HTML::link($this->_getStaticLoader('css', array_keys($cssLinks))) . "\n";
		}
		else {
			foreach ($cssLinks as $href => $attrs) {
				$attrs['href'] = ($this->_config['views.static_loader'] == 2 && $this->_isStatic($href)) ?
							HTML::src($href) : $href;
				$html .= HTML::element('link', $attrs) . "\n";
			}
		}

		foreach (array_merge(self::$_globalLinks, $this->_links) as $href => $attrs) {
			$html .= HTML::element('link', $attrs) . "\n";
		}
		
		if ($this->_favIcon) {
			$_src = htmlspecialchars(($this->_config['views.static_loader'] == 2 && $this->_isStatic($this->_favIcon)) ?
						HTML::src($this->_favIcon) : $this->_favIcon, ENT_COMPAT);
			$html .=<<<HTML
<link rel="shortcut icon" href="$_src" />

HTML;
		}

		$externalJS = array();
		$js = $this->_checkDefaultDir(array_merge(self::$_globalJS, $this->_js), 'js');
		foreach ($js as $src => $attrs) {
			if ($attrs['inline']) {
				$html .=<<<HTML
<script type="{$attrs['type']}">/*<![CDATA[*/
{$this->_getFileSource($src)}
/*]]>*/</script>

HTML;
			}
			else {
				
				if($this->_config['views.static_loader'] == 1){
					$externalJS[$src] = $attrs;
				}else{
					$_src = ($this->_config['views.static_loader'] == 2 && $this->_isStatic($src)) ? HTML::src($src) : $src;				
					$html .= HTML::element('script', array_merge(array('src' => $_src), $attrs), '') . "\n";
				}
				
			}
		}
		
		if (!empty($externalJS) && $this->_config['views.static_loader'] == 1) {
			// single request
			$html .= HTML::script($this->_getStaticLoader('js', array_keys($externalJS))) . "\n";
		}		
		
		
		/************************************************************/
		/*                     OLD CODE                             */
		/************************************************************/
		/*
		if (!empty($externalJS) && $this->_config['views.static_loader'] == 1) {
			// single request
			$html .= HTML::script($this->_getStaticLoader('js', array_keys($externalJS))) . "\n";
		}
		else {			
			foreach ($externalJS as $src => $attrs) {
				$_src = ($this->_config['views.static_loader'] == 2 && $this->_isStatic($src)) ? HTML::src($src) : $src;				
				$html .= HTML::element('script', array_merge(array('src' => $_src), $attrs), '') . "\n";
			}
		}
		*/
    	/************************************************************/

		$jsVars = array_merge(self::$_globalJSVars, $this->_jsVars);

		if (!empty($jsVars) || !empty($this->_domOnLoad)) {
			$html .=<<<HTML
<script type="text/javascript">/*<![CDATA[*/

HTML;
			foreach ($jsVars as $name => $value) {
				$html .=<<<HTML
$name = $value;\n
HTML;
			}

			if (!empty($this->_domOnLoad)) {
				$domLoadedCode = implode('; ', $this->_domOnLoad) . ';';
				$html .= str_replace('%s', 'function() { ' . $domLoadedCode . ' }', $config['views.js_domload_trigger']) . "\n";
			}

			$html .=<<<HTML
/*]]>*/</script>
HTML;
		}

		$headContent = self::$_globalHeadContent . $this->_headExtraContent;
		if ($headContent) $html .= "\n$headContent\n";
		
		$bodyAttributes = HTML::buildAttrs($this->_bodyAttributes);
		$_bodyOnload = $this->_bodyOnload ? ' onload="'.$this->_bodyOnload.'"' : '';
		$html .=<<<HTML

</head>
<body{$_bodyOnload}{$bodyAttributes}>

HTML;
		return $html;
	}

	/**
	 * Get source for the last part of the document.
	 *
	 * @return string
	 */
	public function endDocument()
	{
		$html = "\n</body>\n</html>";
		return $html;
	}

	/**
	 * Set document syntax using any of the class constants SYNTAX_*
	 *
	 * @param int $syntax
	 */
	public function setDocumentSyntax($syntax)
	{
		$this->_syntax = $syntax;
	}

	/**
	 * Setup basic document properties.
	 *
	 * @param string $docType
	 * @param boolean $showXMLDef
	 * @param string $xmlVersion
	 * @return void
	 */
	public function setupDocument($docType, $showXMLDef = false, $xmlVersion = '1.0')
	{
		$this->_docType = $docType;
		$this->_showXMLDef = $showXMLDef;
		$this->_xmlVersion = $xmlVersion;
	}

	/**
	 * Set attribute for the document element (<html> tag).
	 * Value will be automatically escaped when the document is rendered.
	 *
	 * @param string $attribute
	 * @param string $value
	 * @return void
	 */
	public function setDocumentElementAttribute($attribute, $value)
	{
		$this->_docElmAttributes[$attribute] = $value;
	}

	/**
	 * Set attribute for <body> element.
	 * Value will be automatically escaped when the document is rendered.
	 *
	 * @param string $attribute
	 * @param string $value
	 * @return void
	 */
	public function setBodyAttribute($attribute, $value)
	{
		$this->_bodyAttributes[$attribute] = $value;
	}

	/**
	 * Add CSS/JS files. Valid extensions are .css and .js
	 *
	 * @param string $files A single file or an array of files to be included.
	 * @return void
	 * @throws InvalidFileExtensionException if any file extension is invalid.
	 */
	public function addFile($files)
	{
		$files = (array)$files;
		foreach ($files as $src) {
			$ext = strtolower(substr($src, strrpos($src, '.')+1));
			if ($ext == 'js') {
				$this->addJS($src);
			}
			else if ($ext == 'css') {
				$this->addCSS($src);
			}
			else {
				import("io.InvalidFileExtensionException");
				throw new InvalidFileExtensionException("'$ext' is not a valid extension.");
			}
		}
	}

	/**
	 * Set document title. It is automatically escaped.
	 *
	 * @param string $title If strictly NULL, default title will be used.
	 * @param string $prefix Override {views.title_prefix}. Strictly NULL removes default value.
	 * @param string $suffix Override {views.title_suffix}. Strictly NULL removes default value.
	 * @return void
	 */
	public function setTitle($title, $prefix = '', $suffix = '')
	{
		$config = $this->_config;

		if ($title === null) {
			$this->_title = $config->get('views.default_title');
		}
		else {
			$_p = $prefix === null ? '' : (!$prefix ? $config->get('views.title_prefix') : $prefix);
			$_s = $suffix === null ? '' : (!$suffix ? $config->get('views.title_suffix') : $suffix);
			$this->_title = $_p . $title . $_s;
		}
	}

	/**
	 * Set document type using one of the class' constants.
	 *
	 * @param string $docType
	 * @param int $syntax Document syntax. Use class contants SYNTAX_*
	 * @return void
	 */
	public function setDoctype($docType, $syntax = self::SYNTAX_XHTML1)
	{
		$this->_docType = $docType;
		$this->_syntax = $syntax;
	}

	/**
	 * Set document language. Eg: es, en, es-AR, en_US
	 *
	 * @param string $lang
	 * @return void
	 */
	public function setLang($lang)
	{
		$this->_lang = str_replace('_', '-', $lang);
	}

	/**
	 * Set document character set used in  <meta> elements.
	 *
	 * @param string $charset
	 * @return void
	 */
	public function setCharset($charset)
	{
		$this->_charset = $charset;
	}

	/**
	 * Set document keywords. Accepts a string or an array of strings.
	 *
	 * @param string|string[] $keywords
	 * @return void
	 */
	public function setKeywords($keywords)
	{
		$this->_keywords = (array)$keywords;
	}

	/**
	 * Add document keywords. Accepts a string or an array of strings.
	 *
	 * @param string|string[] $keywords
	 * @param boolean $append Determines if the keywords are appended at the end or at the beggining.
	 * @return void
	 */
	public function addKeywords($keywords, $append = true)
	{
		$keywords = (array)$keywords;
		if ($append) $this->_keywords = array_merge($this->_keywords, $keywords);
		else array_splice($this->_keywords, 0, 0, $keywords);
	}

	/**
	 * Set document description. Text is escaped automatically.
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->_description = $description;
	}

	/**
	 * Add document description. Text is escaped automatically.
	 *
	 * @param string $description
	 * @return void
	 */
	public function addDescription($description)
	{
		$this->_description .= $this->_description ? " " . $description : $description;
	}

	/**
	 * Set favorite icon
	 *
	 * @param string $favIcon Image path
	 * @return void
	 */
	public function setFavIcon($favIcon)
	{
		$this->_favIcon = $favIcon;
	}

	/**
	 * Add <meta> tag with the 'name' attribute.
	 * Keywords and description tags are automatically added.
	 * @see #setKeywords
	 * @see #setDescription
	 *
	 * @param string $name Value for 'name' attribute.
	 * @param string $content Value for 'content' attribute.
	 * @param string $lang Content language.
	 * @param string $charset Character set.
	 * @return void
	 */
	public function addMeta($name, $content, $lang = null, $charset = null)
	{
		$this->_metaTags[] = array(
			'name' 		=> $name,
			'content' 	=> $content,
			'lang'		=> $lang,
			'charset'	=> $charset
		);
	}

	/**
	 * Add <link> tag(s). href and title attributes are automatically escaped.
	 *
	 * @param string|string[] $href
	 * @param string $type
	 * @param string $rel
	 * @param string $title
	 * @param string $id
	 * @param string $media
	 * @param string $hreflang
	 * @param boolean $prepend
	 * @return void
	 */
	public function addLink($href, $type = 'text/css', $rel = 'stylesheet', $title = null,
								$id = null, $media = null, $hreflang = null, $prepend = false)
	{
		$data = array();
		foreach ((array)$href as $link) {
			$data[$link] = array(
				'type'		=> $type,
				'rel'		=> $rel,
				'href'		=> $link,
				'title'		=> $title,
				'id'		=> $id,
				'media'		=> $media,
				'hreflang'	=> $hreflang
			);
		}
		
		if ($prepend) $this->_links = array_merge($data, $this->_links);
		else $this->_links = array_merge($this->_links, $data);
	}

	/**
	 * Remove previously added <link> tag. Searches by its href attribute (case sensitive)
	 *
	 * @param string $href
	 * @return void
	 */
	public function removeLink($href)
	{
		unset($this->_links[$href]);
	}

	/**
	 * Remove all links.
	 * 
	 * @return void
	 */
	public function removeLinks()
	{
		$this->_links = array();
	}

	/**
	 * Remove previously added css file. Searches by its href attribute (case sensitive)
	 *
	 * @param string $href
	 * @return void
	 */
	public function removeCSS($href)
	{
		unset($this->_css[$href]);
	}

	/**
	 * Add CSS stylesheet.
	 * Files starting with "@" will be prepended with the default css directory path.
	 *
	 * @param string|string[] $href A single file or an array of files.
	 * @param string $title
	 * @param string $id
	 * @param string $media
	 * @return void
	 */
	public function addCSS($href, $title = null, $id = null, $media = null)
	{
		$data = array();
		foreach ((array)$href as $link) {
			$data[$link] = array(
				'href'		=> $link,
				'type'		=> 'text/css',
				'rel'		=> 'stylesheet',
				'title'		=> $title,
				'id'		=> $id,
				'media'		=> $media
			);
		}

		$this->_css = array_merge($this->_css, $data);
	}

	/**
	 * Prepend CSS stylesheet.
	 *
	 * @param string|string[] $href A single file or an array of files.
	 * @param string $title
	 * @param string $id
	 * @param string $media
	 * @return void
	 */
	public function prependCSS($href, $title = null, $id = null, $media = null)
	{
		$data = array();
		foreach ((array)$href as $link) {
			$data[$link] = array(
				'href'		=> $link,
				'type'		=> 'text/css',
				'rel'		=> 'stylesheet',
				'title'		=> $title,
				'id'		=> $id,
				'media'		=> $media
			);
		}

		$this->_css = array_merge($data, $this->_css);
	}

	/**
	 * Add external Javascript file or embed its code inside script tags.
	 * Files starting with "@" will be prepended with the default js directory path.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param boolean $inline Embed the JS file source inside script tags.
	 * @param string $type
	 * @return void
	 */
	public function addJS($src, $inline = false, $type = 'text/javascript')
	{
		foreach ((array)$src as $file) {
			$this->_js[$file] = array(
				'type'		=> $type,
				'inline'	=> $inline,
				'async'		=> null,
				'defer'		=> null,
				'onload'	=> null
			);
		}
	}
	
	/**
	 * Add external Javascript file or embed its code inside script tags.
	 * Files starting with "@" will be prepended with the default js directory path.
	 * Sets the "async" attribute for the generated script.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param string $onload Javascript code to be executed on script load.
	 * @param boolean $defer Sets the "defer" attribute. Script will be executed after the page has loaded.
	 * @param string $type
	 * @return void
	 */
	public function addAsyncJS($src, $onload = null, $defer = false, $type = 'text/javascript')
	{
		foreach ((array)$src as $file) {
			$this->_js[$file] = array(
				'type'		=> $type,
				'inline'	=> false,
				'async'		=> true,
				'defer'		=> $defer,
				'onload'	=> $onload
			);
		}
	}

	/**
	 * Prepend external Javascript file or embed its code inside script tags.
	 *
	 * @param string|string[] $src A single file or an array of files.
	 * @param boolean $inline Embed the JS file source inside script tags.
	 * @param string $type
	 * @return void
	 */
	public function prependJS($src, $inline = false, $type = 'text/javascript')
	{
		$data = array();
		foreach ((array)$src as $file) {
			$data[$file] = array(
				'type'		=> $type,
				'inline'	=> $inline,
				'async'		=> null,
				'defer'		=> null,
				'onload'	=> null
			);
		}
		$this->_js = array_merge($data, $this->_js);
	}

	/**
	 * Add RSS link.
	 *
	 * @param string $href
	 * @param string $title
	 * @return void
	 */
	public function addRSS($href, $title = null)
	{
		$this->addLink($href, 'application/rss+xml', 'alternate', $title);
	}

	/**
	 * Add global JS var transforming PHP values into JS values using json_encode().
	 * If a global JS var with the same name has been previously added, it will be overwritten.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setJSVar($name, $value = '')
	{
		$this->_jsVars[$name] = json_encode($value);
	}

	/**
	 * Add multiple JS vars transforming PHP values into JS values using json_encode().
	 * Keys are variable names.
	 * If a global JS var with the same name has been previously added, it will be overwritten.
	 *
	 * @param mixed[] $vars
	 * @return void
	 */
	public function setJSVars(array $vars)
	{
		foreach ($vars as $name => $value) {
			$this->setJSVar($name, $value);
		}
	}

	/**
	 * Add <head> extra content
	 *
	 * @param string $str
	 * @return void
	 */
	public function addHeadContent($str)
	{
		$this->_headExtraContent .= $str;
	}

	/**
	 * Set the "profile" attribute of the <head> element
	 *
	 * @param string $str
	 * @return void
	 */
	public function setHeadProfile($attrValue)
	{
		$this->_headProfileAttr = htmlspecialchars($attrValue, ENT_COMPAT);
	}

	/**
	 * Add conditional comment, which is parsed by Internet Explorer.
	 * Detects css and js files by their extension.
	 *
	 * @param string $condition Condition. Eg: lt IE 7
	 * @param string[] $files CSS/JS files to be included inside the condition.
	 * @return void
	 */
	public function addConditionalComment($condition, array $files = array())
	{
		$html = "<!--[if $condition]>\n";
		foreach ($files as $key => $src) {
			$ext = strtolower(substr($src, strrpos($src, '.')+1));
			if ($ext == 'css') {
				$html .= '<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($src, ENT_COMPAT).'" />';
			}
			else if ($ext == 'js') {
				$html .= '<script type="text/javascript" src="'.htmlspecialchars($src, ENT_COMPAT).'"></script>';
			}
		}
		$html .= "\n<![endif]-->\n";
		$this->addHeadContent($html);
	}

	/**
	 * Add JS code to be executed when the document body is loaded (onload event)
	 *
	 * @param string $jsCode
	 * @return void
	 * @see #addDOMOnload
	 */
	public function addBodyOnload($jsCode)
	{
		$jsCode = str_replace('"', "'", $jsCode);
		$this->_bodyOnload .= ($this->_bodyOnload != '' && !preg_match("#; *$#", $this->_bodyOnload)) ? ';'.$jsCode : $jsCode;
	}

	/**
	 * Add JS code to be executed when the DOM is loaded (DOMContentLoaded event)
	 *
	 * @param string $jsCode
	 * @return void
	 * @see #addBodyOnload
	 */
	public function addDOMOnload($jsCode)
	{
		$this->_domOnLoad[] = rtrim($jsCode, '; ');
	}

	/**
	 * Redirect to another URL using <meta> tag
	 *
	 * @param string $redirUrl
	 * @param integer $redirTime Time in seconds. Default is 2.
	 * @return void
	 */
	public function setRedirect($redirUrl = 'index.php', $redirTime = 2)
	{
		$this->addHeadContent('<meta http-equiv="refresh" content="'.$redirTime.'; url='.$redirUrl.'" />');
	}

	/**
	 * Set text direction. Changes 'dir' attribute in <html> tag.
	 *
	 * @param string $dir
	 * @return void
	 */
	public function setTextDir($dir)
	{
		$this->_textDir = $dir;
	}

	/**
	 * Render document. Returns the HTML source.
	 *
	 * @param boolean|callback $filter If a callback is provided it will be called with the rendered html source as the first parameter. If TRUE, new lines and tabs will be removed.
	 * @return string
	 * @override
	 */
	public function render($filter = false)
	{
		$html = $this->beginDocument();
		if (!empty(self::$_headers)) {
			$this->_templates = array_merge(self::$_headers, $this->_templates);
		}
		if (!empty(self::$_footers)) {
			$this->_templates = array_merge($this->_templates, self::$_footers);
		}
		if (!empty($this->_templates)) {
			$tplHTML = $this->_renderTemplate($this->_templates);
			if (is_callable($filter)) $html .= call_user_func($filter, $tplHTML);
			else if ($filter === true) $html .= str_replace(array("\n", "\t", "\r"), '', $tplHTML);
			else $html .= $tplHTML;
		}
		$html .= $this->endDocument();
		return $html;
	}

	public function getTitle()
	{
		return $this->_title;
	}

	public function getFavIcon()
	{
		return $this->_favIcon;
	}

	public function getCharset()
	{
		return $this->_charset;
	}

	public function getLang()
	{
		return $this->_lang;
	}

	public function getKeywords()
	{
		return $this->_keywords;
	}

	public function getDescription()
	{
		return $this->_description;
	}

	public function getLinks()
	{
		return $this->_links;
	}

	public function getJS()
	{
		return $this->_js;
	}

	public function getCSS()
	{
		return $this->_css;
	}

	public function getJSVars()
	{
		return $this->_jsVars;
	}

	public function getHeadContent()
	{
		return $this->_headExtraContent;
	}
}
?>
