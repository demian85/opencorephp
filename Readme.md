PHP 5.3+ Lightweight MVC fully object oriented framework
=============

[Documentación en español](https://docs.google.com/document/pub?id=1JWwllcZs-qsHTRpccbwL04dhrSipX6ypAfTYfZB93DE)

Framework aims to be simple with the minimum required tools.
Source code is 99% documented in english but as you can see, there is no generated php-doc. I'll try to upload it later.
Original documentation is in Spanish. Below is a simplified english translation. I hope you can understand the basics :)

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

Example URL's
----
1. http://www.opencorephp.com/carrier/period/4/edit/param1:value1/param2:value2 
* http://www.opencorephp.com/carrier/period/4/edit/name:rambo 
* http://www.opencorephp.com/carrier/period-manager/edit/5 
* http://www.opencorephp.com/CARRIER/PeRiodS == http://opencorephp.com/carrier/periods 
* http://www.opencorephp.com/users/8 
* http://www.opencorephp.com/users/john-is-gosu
* http://www.opencorephp.com/inexistent-controller-8 

Explanation
---
* Named params like "param:value" are allowed. (Deprecated)
* Named params are not taken into account when building up the route. Those can be accesed using the getParam() method of the Request Class.
* Params that determine the controller or action are case insensitive. Those are automatically formatted to use framework conventions. You can instead use compound words separated by dashes or underscores. Eg: "manage-periods" will be translated into "ManagePeriods".
* Default controllers per module are declared in the config file "core.php". those names must denote a valid class name.
* Preferably, URL's should be lowercase.
* In example 5: when he action is a number, the default action gets called by passing that number as the first argument.
* In example 6: using the previous example, you can override the controller's method "actionError", which gets called when a non-existent action is requested and perform a custom action.
* In example 7, which is similar as the previous example, you can override method "controllerError" in the default controller for the detected module to take a custom action when a non-existent controller is reuested. By default, a redirection to the default controller is performed.
* Controllers and actions starting with an underscore are not allowed!

Aliases
----
* You can define URL aliases in the config file "routes.php"
* You define an array where keys are regular expressions and values their replacement.
* The requested route will be searched for aliases and the replacement will be applied. The result will be used to determine module/controller/action.

Configuration
----
App config is divided into files under the application/config folder. Each one returns an associative array.
You can have specific config files per domain by creating a folder with its full or partial name inside the config folder.
You then access config values easily with the "Config" class, which implements the ArrayAccess interface.
To acces a config value, you prepend the file name before the key.
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
* DEBUG_MODE: Shows detailed info about an error/exception when possible. Also enabled Firebug and ChromePHP.
* IN_PRODUCTION: If this is true, error and exception information is never sent to the browser. If DEBUG_MODE is enabled you can still view errors in the browser console using Firebug or ChromePHP.
* APPLICATION_DIR: Absolute path to "application" directory.
* FRAMEWORK_DIR: "Absolute path to framework directory.