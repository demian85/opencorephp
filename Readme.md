PHP 5.3+ Lightweight MVC fully object oriented framework
=============

[Documentación en español](https://docs.google.com/document/pub?id=1JWwllcZs-qsHTRpccbwL04dhrSipX6ypAfTYfZB93DE)

Characteristics
-------------
* Based on MVC pattern, fully compatible with PHP 5.3 E_STRICT.
* Automatic friendly URL'S routing.
* Easy configuration files per domain. App config is managed by the Config class preventing global variables and constants.
* Localization/internationalization support: automatic URL redirection based on language or country, super-easy native translation system better than Poedit!
* Databse access abstraction layer (currently MySQL implemented based on mysqli lib)
* Excelent error/exception handling, support for Firebug/ChromePHP logging. Logger class also supports database, file and email logging. Does not show HTML when executed from the command line!
* Intelligent Document class that allows creating (X)HTML(5) documents dynamically adding files. Supports static file loading using subdomains.
* Event support.

Conventions
---------
The framework uses the same language conventions as Java/Javascript. CamelCased classes, methods and variables.
Private and protected properties/methods start with an underscore.

Project Structure
-------
<pre>
/
	application
		cache
		classes
		config
		controllers
		functions
		i18n
		locale
		logs
		models
		scripts
		views
		bootstrap.php (framework initialization)
	public (Document Root: name and structure of this folder can change)
		scripts
		styles
		images
		.htaccess
		index.php
		static.php (static file loading optimization)
</pre>

Routing
----
The framework works strictly with friendly URL's. The route represents a module/controller/action and additional parameters.
A controller is a class extending from "Controller" and an action is a method of that class.
Modules are simply folders inside the controller directory and allows you to group controllers. You can create any number of modules recursively.

Configuration
----
App config is divided into files under the application/config folder. Each one returns an associative array.
You can have specific config files per domain by creating a folder with its full or partial name inside the config folder.
You then access config values easily with the "Config" class, which implements the ArrayAccess interface.
Yo acces a config value, you prepend the file name before the key.
Eg:
<pre>
// to access the value "domain" within the core.php config file
$config = Config::getInstance();
$config->get("core.domain");
// or..
$config['core.domain'];
</pre>


Constants defined in bootstrap.php
----
DEBUG_MODE: Shows detailed info about an error/exception when possible. Also enabled Firebug and ChromePHP.
IN_PRODUCTION: If this is true, error and exception information is never sent to the browser. If DEBUG_MODE is enabled you can still view errors in the browser console using Firebug or ChromePHP.
APPLICATION_DIR: Absolute path to "application" directory.
FRAMEWORK_DIR: "Absolute path to framework directory.