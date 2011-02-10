<?php

//namespace gui;

/**
 * This class renders a view.
 * A view can include any number of templates.
 * 
 * @package gui
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class View
{
	/**
	 * @var Request
	 */
	protected $_request;
	/**
	 * @var Config
	 */
	protected $_config;
	/**
	 * Array with template names.
	 * @var string[]
	 */
	protected $_templates;
	/**
	 * Array with template data.
	 * @var mixed[]
	 */
	protected $_data;
	/**
	 * Global data.
	 * @var mixed[]
	 * @static
	 */
	protected static $_globalData = array();
	
	/**
	 * Render template(s).
	 * Variable precedence is as follows:
	 * - Local data
	 * - View data
	 * - Global data
	 * 
	 * @param string|string[] $templateName A single template or an array of templates.
	 * @param mixed[] $data Array with data. Its values will be extracted and converted into local variables inside the template.
	 * @return string
	 */
	protected function _renderTemplate($templateName, array $data = array())
	{
		$__templateName____ = (array)$templateName;
		$__data____ = array_merge(self::$_globalData, $this->_data, $data);
		unset($templateName, $data);
		extract($__data____, EXTR_SKIP);
		ob_start();
		$__printTplName____ = $this->_config->get('views.print_tpl_name');
		
		foreach ($__templateName____ as $__tpl____) {
			$__file____ = $this->_config->get('views.dir') . DIRECTORY_SEPARATOR . $__tpl____
						. $this->_config->get('views.file_extension');
			if ($__printTplName____) echo "<!-- Begin Template: $__tpl____ -->\n";
			include($__file____);
			if ($__printTplName____) echo "\n<!-- End Template: $__tpl____ -->";
			echo "\n";
		}
		
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	/**
	 * Check if template exists.
	 *
	 * @param string $template
	 * @return void
	 * @throws FileNotFoundException if template doesn't exist.
	 */
	protected static function _checkTemplate($template)
	{
		$config = Config::getInstance();
		$file = $config->get('views.dir') . DIRECTORY_SEPARATOR . $template . $config->get('views.file_extension');
		if (!file_exists($file)) {
			import("io.FileNotFoundException");
			throw new FileNotFoundException("Invalid template: $template. File '$file' not found.");
		}
	}
	
	/**
	 * Set global variable(s) for later usage inside the templates.
	 * If an array is provided, its keys are extracted and converted into variable names inside the template.
	 *
	 * @param string|mixed[] $name A single variable name or an array of names and values.
	 * @param string $value
	 * @return void
	 */
	public static function setGlobal($name, $value = "")
	{
		if (is_array($name)) {
			self::$_globalData = array_merge(self::$_globalData, $name);
		}
		else {
			self::$_globalData[$name] = $value;
		}
	}
	
	/**
	 * Constructor. Creates a view that will render the specified templates.
	 *
	 * @param string|string[] $template A single template name or an array of templates.
	 * @param mixed[] $data Data array where keys are valid variable names. They will be extracted and converted into local variables for each template.
	 * @throws FileNotFoundException if $template does not represent an existent template file.
	 */
	public function __construct($template = null, array $data = array())
	{
		$this->_templates = (array)$template;
		foreach ($this->_templates as $tpl) {
			self::_checkTemplate($tpl);
		}
		
		$this->_data = $data;
		$this->_config = Config::getInstance();
		$this->_request = Request::getInstance();
	}
	
	/**
	 * Get a DocumentView instance that includes this view's templates and data.
	 *
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public function getDocument($title = null)
	{
		import('gui.DocumentView');
		return new DocumentView($this->_templates, $title, $this->_data);
	}

	/**
	 * Add template(s).
	 *
	 * @param string|string[] $template A single template or an array of templates.
	 * @return void
	 */
	public function addTemplate($template)
	{
		$templates = (array)$template;
		foreach ($templates as $tpl) {
			self::_checkTemplate($tpl);
		}
		$this->_templates = array_merge($this->_templates, $templates);
	}
	
	/**
	 * Get templates
	 * @return string[]
	 */
	public function getTemplates()
	{
		return $this->_templates;
	}
	
	/**
	 * Set variable(s) for later usage inside the templates.
	 * If an array is provided, its keys are extracted and converted into variable names inside the template.
	 * Local variables replaces global variables.
	 *
	 * @param string|mixed[] $name A single variable name or an array of names and values.
	 * @param string $value
	 * @return void
	 */
	public function set($name, $value = "")
	{
		if (is_array($name)) {
			$this->_data = array_merge($this->_data, $name);
		}
		else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Renders the registered templates.
	 *
	 * @param boolean|callback $filter If a callback is provided it will be called with the rendered html source as the first parameter. If TRUE, new lines and tabs will be removed.
	 * @return string
	 */
	public function render($filter = false)
	{
		$html = '';
		if (!empty($this->_templates)) {
			$html .= $this->_renderTemplate($this->_templates);
		}
		if (is_callable($filter)) $html = call_user_func($filter, $html);
		else if ($filter === true) $html = str_replace(array("\n", "\t", "\r"), '', $html);
		return $html;
	}

	/**
	 * Get template variables.
	 * 
	 * @return mixed[]
	 */
	public function getData()
	{
		return $this->_data;
	}
	
	/**
	 * Magic mathod to dinamically set template data.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->_data[$name] = $value;
	}
	
	/**
	 * Magic method to fetch a variable previously assigned.
	 * If variable does not exist, an empty string is returned.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return isset($this->_data[$name]) ? $this->_data[$name] : '';
	}
	
	/**
	 * Returns view's HTML source.
	 * Note that exceptions thrown within any template will be caught and sent to the registered exception handler.
	 *
	 * @return string
	 */
	public function __toString()
	{
		try {
			$source = $this->render();
		} catch (Exception $ex) {
			$config = Config::getInstance();
			try {
				if (!($ex instanceof LoggerException) && $config->get('logs.log_exceptions')) {
					import('log.Logger');
					Logger::getInstance()->log($ex, Logger::TYPE_EXCEPTION);
				}
			} catch (Exception $ex2) { }
			$call = $config->get('core.exception_handler');
			if ($call && is_callable($call)) {
				call_user_func($call, $ex);
			}
			$source = "";
		}
		return $source;
	}
}
?>
