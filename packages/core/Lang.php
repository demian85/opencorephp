<?php

// namespace core;

/**
 * This class manages the framework internationalization and includes a few useful static methods.
 * When a phrase is requested, the file which contains its definition is loaded.
 *
 * @package core
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Lang
{
	/**
	 * @var Lang
	 * @static
	 */
	protected static $_instance;
	/**
	 * Directory where i18n files are located.
	 * @var string
	 */
	protected $dir;
	/**
	 * Default catalog name.
	 * @var string
	 */
	protected $defaultCatalog;
	/**
	 * Matrix with phrase translations per catalog.
	 * @var string[][]
	 */
	protected $data = array();
	/**
	 * @var string
	 */
	protected $defaultLocale = null;

	/**
	 * Constructor.
	 *
	 */
	protected function __construct()
	{
		$config = Config::getInstance();
		$this->defaultLocale = $config->get('app.locale');
		$this->dir = $config->get('i18n.dir');
		$this->setDefaultCatalog($config->get('i18n.default_catalog'));
	}

	/**
	 * Get the first matching folder for the specified locale.
	 *
	 * @param string $locale
	 * @return string Folder name.
	 */
	protected function _getFolderByLocale($locale)
	{
		$dir = $this->dir . DIRECTORY_SEPARATOR . $locale;
		if (!is_dir($dir)) {
			$lang = preg_split('#[_-]#', $locale, -1, PREG_SPLIT_NO_EMPTY);
			$d = new DirectoryIterator($this->dir);
			foreach ($d as $file) {
				if ($d->isDir() && stripos($file, $lang[0]) === 0) {
					$dir = $this->dir . DIRECTORY_SEPARATOR . $file;
					break;
				}
			}
		}

		return $dir;
	}

	/**
	 * Load a specific catalog.
	 *
	 * @param string $locale Locale
	 * @param string $name Catalog name
	 * @return void
	 */
	protected function _loadCatalog($locale, $name)
	{
		$config = Config::getInstance();
		$data = array();
		$dir = $this->_getFolderByLocale($locale);
		$file = $dir . DIRECTORY_SEPARATOR . $name . $config->get('i18n.file_extension');
		if (!file_exists($file)) {
			return;
		}
		$lines = file($file, FILE_IGNORE_NEW_LINES);
		$length = count($lines);
		$i = 0;
		while ($i < $length) {
			if (!empty($lines[$i]) && !empty($lines[$i+1])) {
				$data[$lines[$i]] = $lines[$i+1];
			}
			$i += 3;
		}
		$this->data[$name] = $data;
	}

	/**
	 * Returns an instance of this class.
	 *
	 * @return Lang
	 */
	public static function getInstance()
	{
		if (self::$_instance == null) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Search {i18n.language_map} for a suitable language for the specified country.
	 *
	 * @param string $countryCode Uppercase country code.
	 * @param string $defaultLanguage Default lowercase language code.
	 * @return string
	 */
	public static function getLanguageByCountry($countryCode, $defaultLanguage = 'en')
	{
		$language = null;
		$globalLang = null;
		$config = Config::getInstance();

		foreach ((array)$config->get('i18n.language_map') as $lang => $countries) {
			if ($countries === null && !$globalLang) {
				$globalLang = $lang;
			}
			else if (in_array($countryCode, (array)$countries)) {
				$language = $lang;
				break;
			}
		}

		if (!$language) {
			$language = $globalLang ? $globalLang : $defaultLanguage;
		}

		return $language;
	}

	/**
	 * Parse a locale and return an array with the following keys:
	 * - language : the language part (lowercase language code)
	 * - country : the country part (uppercase country code) - if present
	 * Example of valid formats: es_AR, en-US, pt, en
	 *
	 * @param string $locale
	 * @return string[]
	 * @throws InvalidArgumentException if $locale format is invalid.
	 */
	public static function parseLocale($locale)
	{
		if (preg_match("#^([a-z]{2})(?:[_-]([A-Z]{2}))?$#", $locale, $matches)) {
			return array(
				'language'	=> $matches[1],
				'country'	=> isset($matches[2]) ? $matches[2] : ''
			);
		}

		throw new InvalidArgumentException("\"$locale\" is not a valid locale format.");
	}

	/**
	 * Setup Gettext module.
	 *
	 * @param string $domain Domain. The name of the .mo archive which contains the translations.
	 * @param string $localeDir Directory where locales are located. Default is "../application/locale"
	 * @return void
	 */
	public static function setupGettext($domain = 'default', $localeDir = null)
	{
		if (!$localeDir) $localeDir = APPLICATION_DIR . '/locale';
		$config = Config::getInstance();
		bindtextdomain($domain, $localeDir);
		bind_textdomain_codeset($domain, $config->get('core.encoding'));
		textdomain($domain);
	}

	/**
	 * Set default catalog.
	 *
	 * @param string $name Catalog name without its extension.
	 * @return void
	 * @throws InvalidArgumentException if catalog name is null
	 */
	public function setDefaultCatalog($name)
	{
		if (!$name) throw new InvalidArgumentException("Catalog name cannot be null.");
		$this->defaultCatalog = $name;
	}

	/**
	 * Get translated phrase. {app.locale} is used as the default locale if omitted.
	 * If phrase has no translation or the catalog does not exist for the specified locale, the same input string is returned.
	 *
	 * @param string $phrase A single phrase.
	 * @param string $catalog NULL indicates default catalog extracted from {i18n.default_catalog}
	 * @param string $locale NULL indicates default locale extracted from {app.locale}
	 * @return string
	 */
	public function get($phrase, $catalog = null, $locale = null)
	{
		if ($catalog == null) {
			$catalog = $this->defaultCatalog;
		}
		if ($locale == null) {
			$locale = $this->defaultLocale;
		}

		if (!isset($this->data[$catalog])) {
			$this->_loadCatalog($locale, $catalog);
		}

		$value = isset($this->data[$catalog][$phrase]) ? $this->data[$catalog][$phrase] : $phrase;

		return $value;
	}

	/**
	 * Get an array of translated phrases. {app.locale} is used as the default locale if omitted.
	 *
	 * @param string[] $phrase An array of phrases.
	 * @param string $locale NULL indicates default locale extracted from {app.locale}
	 * @return string[]
	 * @throws FileNotFoundException if unable to load the file which contains the phrase definition.
	 */
	public function getArray(array $phrases, $locale = null)
	{
		$values = array();
		foreach ($phrases as $ph) {
			$values[] = $this->get($ph, $locale);
		}
		return $values;
	}
}
?>