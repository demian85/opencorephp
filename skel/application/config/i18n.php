<?php

/**
 * Internationalization config file.
 */
return array(
/**
 * Directory where i18n files are located (without trailing slash).
 */
'dir'	=> APPLICATION_DIR . '/i18n',
/**
 * Extension for internationalization files. Any extension that can be handled by the Properties class (php or ini)
 */
'file_extension'	=> '.cat',
/**
 * Default catalog name that will be used for translation.
 */
'default_catalog'	=> 'default',
/**
 * System valid locales.
 * The method Client::getLocaleInfo() returns the most suitable value based on the client's information.
 * If the locale is not listed in the array below, the comparison is made using the language prefix.
 * The priority is given by the order of the elements.
 * The first value is used as a fallback.
 * Eg: 	being $valid_locales = array('es_AR', 'en_US', 'pt_BR') and the client's detected locale: "es_UY",
 * 		the returned value will be "es_AR".
 */
'locales'		=> array('en_US', 'es_AR', 'pt_BR'),
/**
 * Array that maps languages to countries.
 * The method Client::getLocaleInfo() detects the client's language based on the values listed below.
 * You provide language codes as array keys and an array of countries as values.
 * Country codes must be uppercase.
 * A value of NULL represents any country.
 */
'language_map'		=> array(
		'es'	=> array('ES','AR','BO','EC','CO','CU','DO','VE','CR','GT','HT','HN','MX',
							'NI','PR','SV','UY','GY','PA'),
		'pt'	=> array('BR','PT'),
		'en'	=> null
	)
);
?>