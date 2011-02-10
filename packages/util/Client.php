<?php

//namespace util;

/**
 * This class has useful methods for fetching client's information.
 * 
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
final class Client
{
	private function __construct() { }
	
	private static function _filterLangs($lang)
	{
		return preg_match("#_[a-z]+#i", $lang);
	}
	
	/**
	 * Detect client's supported languages and optionally return their associated country code when possible (locale).
	 * Country codes are always uppercase.
	 *
	 * @param boolean $getCountryCode Return language associated country code as a suffix separated by a dash "_". Eg: AR, US, FR.
	 * @return string[] 
	 */
	public static function getSupportedLanguages($getCountryCode = false)
	{
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return array();
		
		$langs = explode(",", strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		foreach ($langs as &$lang) {
			$lang = preg_replace("#;q=[\\d.]+#", "", $lang);
			if (!$getCountryCode) {
				$lang = preg_replace("#[_-]([a-z]+)$#i", "", $lang);
			}
			else {
				$lang = preg_replace("#[_-]([a-z]+)$#ie", "'_'.strtoupper('$1')", $lang);
			}
		}
		$langs = array_values(array_unique($langs));
		
		return str_replace("-", "_", $langs);
	}
	
	/**
	 * Get client's supported locales.
	 *
	 * @return string[]
	 */
	public static function getSupportedLocales()
	{
		return array_values(array_filter(self::getSupportedLanguages(true), array(__CLASS__, '_filterLangs')));
	}
	
	/**
	 * Get client's country ISO code. Uses GeoIP extension when possible.
	 * Returns NULL if no country code could be found.
	 *
	 * @param string $default Default country code
	 * @return string|null
	 */
	public static function getCountryCode($default = 'US')
	{
		if (function_exists('geoip_country_code_by_name')) {
			$code = @geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
		}
		else if (isset($_SERVER["GEOIP_COUNTRY_CODE"]) && !empty($_SERVER["GEOIP_COUNTRY_CODE"])) {
			$code = $_SERVER["GEOIP_COUNTRY_CODE"];
		}
		else {
			$code = null;
		}
		
		if (!$code) {
			$_locales = self::getSupportedLocales();
			if (!empty($_locales)) {
				$_tmp = explode("_", $_locales[0]);
				$code = $_tmp[1];
			}
		}
		
		return $code ? strtoupper($code) : $default;
	}
	
	/**
	 * Returns locale information using client's country code.
	 * The language for the country is taken out from {i18n.language_map}.
	 * If it does not exist, $defaultLanguage is used.
	 * Searches {i18n.locales} for the most suitable locale based on the language/country information.
	 *
	 * @param string $fromLanguage Force language code and search only for a suitable country.
	 * @param string $defaultLanguage Default language (lowercase)
	 * @param string $defaultCountry Default country code (uppercase)
	 * @return string[] An array with the following keys:
	 * 					- language : the language code detected for the country
	 * 					- country : the country code
	 * 					- locale : one of the locales from {i18n.valid_locales}
	 * 					- native_locale : the original detected locale, even if it does not exists in {i18n.valid_locales}
	 * @throws InvalidArgumentException if $defaultLanguage or $defaultCountry is null.
	 */
	public static function getLocaleInfo($fromLanguage = null, $defaultLanguage = 'en', $defaultCountry = 'US')
	{
		if ($defaultLanguage == null || $defaultCountry == null) {
			throw new InvalidArgumentException("\$defaultLanguage and \$defaultCountry cannot be null.");
		}
		
		$config = Config::getInstance();
		
		$countryCode = self::getCountryCode($defaultCountry);
		$language = $fromLanguage ? $fromLanguage : Lang::getLanguageByCountry($countryCode, $defaultLanguage);	
		$nativeLocale = $language . "_" . $countryCode;
		$validLocales = (array)$config->get('i18n.locales');
		$locale = null;
		
		if (!in_array($nativeLocale, $validLocales)) {
			$_tmp1 = explode("_", $nativeLocale);
			foreach ($validLocales as $l) {
				$_tmp2 = explode("_", $l);
				if ($_tmp1[0] == $_tmp2[0]) {
					$locale = $l;
					break;
				}
			}
			if (!$locale) {
				$locale = $validLocales[0];
			}
		}
		else {
			$locale = $nativeLocale;
		}
		
		return array(
			'language'		=> $language,
			'country'		=> $countryCode,
			'locale'		=> $locale,
			'native_locale'	=> $nativeLocale
		);
	}
}
?>
