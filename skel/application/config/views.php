<?php


$config = array(
/**
 * Views directory.
 */
'dir'				=> APPLICATION_DIR . '/views',
/**
 * Enable CSS autoload.
 * When a View is instantiated, a CSS file with the same name as the controller or module will be searched inside {views.default_css_dir} using the same module or controller's path.
 * Eg: if the controller "Users" is inside a module called "admin/panel", the CSS file path should be "/admin/panel/users.css".
 * Eg: if the module is "admin", the CSS file path should be "admin.css" and must be located in the same directory as the module (not inside!).
 * CSS file name must be lowercase.
 */
'css_autoload'		=> true,
/**
 * Enable JS autoload. Search method is described above.
 */
'js_autoload'		=> true,
/**
 * Default CSS directory for autoloading (without trailing slash). It should be relative to the document root.
 * CSS stylesheets will be searched inside this directory.
 */
'default_css_dir'	=> '/styles',
/**
 * Default JS directory for autoloading (without trailing slash). It should be relative to the document root.
 * JS files will be searched inside this directory.
 */
'default_js_dir'	=> '/scripts',
/**
 * An array of CSS files that will be included in all the documents.
 */
'global_css'		=> array(
	'/styles/global.css',
),
/**
 * An array of JS files that will be included in all the documents.
 */
'global_js'			=> array(
	'/scripts/mootools-core-1.3-full.js',
	'/scripts/mootools-more.js',
	'/scripts/global.js'
),
/**
 * JS Code that will trigger the DOMContentLoaded event.
 * %s will be replaced with a function containing all the added statements.
 * Eg: "$(document).ready(%s);" will expand to "$(document).ready(function() { <js code> })"
 */
'js_domload_trigger'	=> "addEvent('domready', %s);",

/**
 * Set static loader mode:
 * 0 : disabled
 * 1 : group css/js files into one single request, uses {views.static_loader.url}
 * 2 : alternate static domains for each static content the document loads (css, js and favicon).
 *	   Only works for relative URL's and static content, which is detected by the file extension.
 * See {views.static_loader.domains}
 */
'static_loader'	=> 0,
/**
 * URL that will be used to load static js/css files in one single request.
 * static.php is the default loader provided by the framework. The file should reside in the document root.
 * Parameters accepted:
 * - (string) type : file type: js or css
 * - (string) files : array of files separated by semicolons
 * - (int) compress : strips new lines and tabs from files.	Posible values:
 *						0 = disable
 *						1 = compress css
 *						2 = compress js
 *						3 = compress both
 * Special vars that will be replaced:
 * {%PROTOCOL} : current protocol
 * {%DOMAIN} : domain extracted from {core.domain}, allows you to use subdomains.
 * {%TYPE} : the file type: "js" or "css".
 * {%FILES} : file names separated by semicolons ;
 * Eg: /static.php?type=js&files=%2Fscripts%2Fscript.js%3B%2Fscripts%2Fglobal.js
 */
'static_loader.url'	=> '/static.php?type={%TYPE}&files={%FILES}&compress=1',
/**
 * Domains used by method HTML::src() and for static content used in documents.
 * Domains MUST NOT END WITH A SLASH!!!
 * Special vars that will be replaced:
 * {%PROTOCOL} : current protocol
 * {%DOMAIN} : domain extracted from {core.domain}, allows you to use subdomains.
 */
'static_loader.domains'	=> array(
		'{%PROTOCOL}://static1.{%DOMAIN}',
		'{%PROTOCOL}://static2.{%DOMAIN}',
		'{%PROTOCOL}://static3.{%DOMAIN}',
	),
/**
 * Set expiration date for static loader, in seconds.
 * Expires and Cache-Control headers will be automatically added.
 * 0 disables this option. Recommendation: use Apache or any web server cache module instead!
 */
'static_loader.expires'	=> 0,

/**
 * File extension for view templates.
 */
'file_extension'	=> '.php',
/**
 * Default title to be used if no title has been set when rendering a document. AVOID THIS!!
 */
'default_title'		=> '',
/**
 * Title prefix for all views.
 * HTML special characters will be automatically escaped.
 */
'title_prefix'		=> '',
/**
 * Title suffix for all views.
 * HTML special characters will be automatically escaped.
 */
'title_suffix'		=> '',
/**
 * Default doctype. If empty, HTML5 Doctpe will be used.
 */
'doctype'			=> '',
/**
 * Default favorite icon for rendering views.
 */
'favicon'			=> '/images/favicon.png',
/**
 * Default document keywords
 */
'keywords'			=> '',
/**
 * 404 template, used for invalid requests if {core.controllers.onerror} == 404.
 * If empty, no template is rendered.
 * The class View es used to render the template, so it must be a valid XHTML document.
 */
'tpl_404'			=> '404',
/**
 * Default class that will be used for rendering documents using the static methods provided by the ViewFactory class.
 * The class must exist and extend from DocumentView.
 */
'default_docview_class'	=> 'DocumentView',
/**
 * Template that will be used to render an error message.
 * The method ViewFactory::errorDoc() will pass a variable named 'message' to the template.
 */
'tpl_error'			=> 'error',
/**
 * Template that will be used to render a warning message.
 * The method ViewFactory::warningDoc() will pass a variable named 'message' to the template.
 */
'tpl_warning'		=> 'warning',
/**
 * Template that will be used to render an info message.
 * The method ViewFactory::infoDoc() will pass a variable named 'message' to the template.
 */
'tpl_info'			=> 'info',
/**
 * Template that will be used to render a plain text message.
 * The method ViewFactory::plainDoc() will pass a variable named 'message' to the template.
 */
'tpl_plain'			=> 'plain',
/**
 * When rendering a template inside the View, prints its name as an HTML comment. Useful for debugging.
 */
'print_tpl_name'	=> false
);

return $config;
?>