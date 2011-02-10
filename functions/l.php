<?php

/**
 * Alias of Lang::get()
 *
 * @param string $phrase A single phrase.
 * @param string $catalog NULL indicates default catalog extracted from {i18n.default_catalog}
 * @param string $locale NULL indicates default locale extracted from {app.locale}
 * @return string
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
function l($phrase, $catalog = null, $locale = null) {
	return Lang::getInstance()->get($phrase, $catalog, $locale);
}

?>