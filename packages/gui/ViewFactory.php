<?php

// namespace gui;

import('gui.DocumentView');

/**
 * This class has useful static methods for creating different types of views and documents.
 *
 * @package gui
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
abstract class ViewFactory
{
	protected function __construct() { }
	
	/**
	 * Create an instance of DocumentView that renders an error template.
	 * The template name is taken out from {views.tpl_error}
	 * You can provide a custom class that extends from DocumentView in {views.default_docview_class}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function errorDoc($message, $title = null)
	{
		$config = Config::getInstance();
		$class = $config->get('views.default_docview_class');
		if (!$title) $title = l('Error');
		return new $class($config->get("views.tpl_error"), $title, array('message' => $message));
	}
	/**
	 * Create an instance of DocumentView that renders a warning template.
	 * The template name is taken out from {views.tpl_warning}
	 * You can provide a custom class that extends from DocumentView in {views.default_docview_class}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function warningDoc($message, $title = null)
	{
		$config = Config::getInstance();
		$class = $config->get('views.default_docview_class');
		if (!$title) $title = l('Warning');
		return new $class($config->get("views.tpl_warning"), $title, array('message' => $message));
	}
	/**
	 * Create an instance of DocumentView that renders an info template.
	 * The template name is taken out from {views.tpl_info}
	 * You can provide a custom class that extends from DocumentView in {views.default_docview_class}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function infoDoc($message, $title = null)
	{
		$config = Config::getInstance();
		$class = $config->get('views.default_docview_class');
		if (!$title) $title = l('Information');
		return new $class($config->get("views.tpl_info"), $title, array('message' => $message));
	}
	/**
	 * Create an instance of DocumentView that renders a plain template.
	 * The template name is taken out from {views.tpl_plain}
	 * You can provide a custom class that extends from DocumentView in {views.default_docview_class}
	 *
	 * @param string $message Message that will be shown to the user.
	 * @param string $title Document title
	 * @return DocumentView
	 */
	public static function plainDoc($message, $title = null)
	{
		$config = Config::getInstance();
		$class = $config->get('views.default_docview_class');
		return new $class($config->get("views.tpl_plain"), $title, array('message' => $message));
	}
	
	public static function errorView($message)
	{
		$config = Config::getInstance();
		return new View($config->get("views.tpl_error"), array('message' => $message));
	}
	public static function warningView($message)
	{
		$config = Config::getInstance();
		return new View($config->get("views.tpl_warning"), array('message' => $message));
	}
	public static function infoView($message)
	{
		$config = Config::getInstance();
		return new View($config->get("views.tpl_info"), array('message' => $message));
	}
	public static function plainView($message)
	{
		$config = Config::getInstance();
		return new View($config->get("views.tpl_plain"), array('message' => $message));
	}
	
	/**
	 * Render a "404 not found" view using the template {views.tpl_404}
	 *
	 * @return void
	 */
	public static function notFoundView()
	{
		$config = Config::getInstance();
		header("HTTP/1.0 404 Not Found", true, 404);
		$template404 = $config->get('views.tpl_404');
		if ($template404) {
			echo new View($template404);
		}
		exit;
	}
}
?>
