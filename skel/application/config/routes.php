<?php


return array(
/**
 * Specifies the first route parameter index that will be used for routing.
 * Aliases are resolved before obtaining the parameters.
 * If {routes.language_redirect} is 'param', the language code is excluded.
 * Eg: if requested URL is http://blog.mysite.com/admin/panel and start_index is 1, "admin" will be ignored.
 */
'start_index'	=> 0,

/**
 * Redirect based on client's language or detect language from the URL.
 * This option implicitly enables locale autodetection, even if {core.locale} is empty.
 *
 * Possible values:
 * 'subdomain' : detect language from subdomain. It will be the first label before the domain
 * 					Eg: if URL is http://blog.es.mysite.com, language will be "es"
 * 'param' : detect language from first indexed parameter.
 * 					Eg: if URL is http://www.mysite.com/es/admin/panel, language will be "es"
 * NULL : disable this feature
 */
'language_redirect'		=> '',

/**
 * Route aliases. Keys are regular expressions (including delimiters) and values are replacements.
 * The regular expressions will be tested against requested route (the URI formed by the requested indexed params, excluding GET and named params)
 * When a replacement is done, the remaining aliases will be discarded.
 *
 * Eg: #es/(.*)# => admin/panel/$1
 * When the requested route is "es/user/list", it will be translated as "admin/panel/user/list".
 * Route map is applied on the final route.
 */
'aliases'		=> array(),

/**
 * Route map. Allows creating aliases for modules, controllers and actions.
 * Each item must be an array, where the key represents the language code.
 * Keys are original routes and values are aliases, written the same way as they appear in the URL.
 * The route must not include the language code if {routes.language_redirect} is 'param'.
 * Eg: users => usuarios : creates an alias for module "users" and allows an URL like http://www.hi.com/usuarios
 */
'route_map'		=> array(
			'es'	=> array(

			)
		),

/**
 * Maps subdomains to modules.
 * Keys are subdomain labels separated by dots and values are module names.
 * Eg: "admin.blog" => "blog/admin"
 */
'subdomain_map'	=> array()

);
?>