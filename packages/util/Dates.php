<?php

//namespace util;

/**
 * Utility class for performing useful operations on dates.
 * 
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class Dates {

	const INTERVAL_FORMAT_SINGLE_UNIT = 0;

	const INTERVAL_FORMAT_YEARS = 1;
	const INTERVAL_FORMAT_MONTHS = 2;
	const INTERVAL_FORMAT_DAYS = 4;
	const INTERVAL_FORMAT_HOURS = 8;
	const INTERVAL_FORMAT_MINUTES = 16;
	const INTERVAL_FORMAT_SECONDS = 32;

	const INTERVAL_FORMAT_AUTO = 63;
	
	private function __construct() { }

	/**
	 * Smart date interval formatter. You can specify which units to include using a bit mask and class constants INTERVAL_FORMAT_*
	 * Words are automatically translated according locale.
	 * 
	 * @param DateTime $date1
	 * @param DateTime $date2
	 * @param int $format Default is INTERVAL_FORMAT_AUTO, which shows the first 2 units
	 * @return string
	 */
	static function intervalFormat(DateTime $date1, DateTime $date2 = null, $format = self::INTERVAL_FORMAT_AUTO, $unitSeparator = ', ')
	{
		if ($date2 == null) $date2 = new DateTime();
		$diff = $date1->diff($date2);
		$lang = Lang::getInstance();		

		$plural = function($count, $singular, $plural) {
			return $count . " " . ($count == 1 ? $singular : $plural);
		};

		if ($format == self::INTERVAL_FORMAT_SINGLE_UNIT) {
			$y = floor($diff->days/365);
			$m = floor($diff->days/30);
			$h = floor($diff->days*24);
			$i = floor($diff->days*24*60);
			$s = floor($diff->days*24*3600);
			if ($y >= 1) $str = $plural($y, $lang->get('year'), $lang->get('years')) . " " . $lang->get('approximately');
			else if ($m >= 1) $str = $plural($m, $lang->get('month'), $lang->get('months')) . " " . $lang->get('approximately');
			else if ($diff->days >= 1) $str = $plural($diff->days, $lang->get('day'), $lang->get('days')) . " " . $lang->get('approximately');
			else if ($h >= 1) $str = $plural($h, $lang->get('hour'), $lang->get('hours')) . " " . $lang->get('approximately');
			else if ($i >= 1) $str = $plural($i, $lang->get('minute'), $lang->get('minutes')) . " " . $lang->get('approximately');
			else $str = $lang->get('less than a minute ago');
		}
		else {
			$parts = array();
			
			if ($diff->y > 0 && ($format & self::INTERVAL_FORMAT_YEARS)) $parts[] = $plural($diff->y, $lang->get('year'), $lang->get('years'));
			if ($diff->m > 0 && ($format & self::INTERVAL_FORMAT_MONTHS)) $parts[] = $plural($diff->m, $lang->get('month'), $lang->get('months'));
			if ($diff->d > 0 && ($format & self::INTERVAL_FORMAT_DAYS)) $parts[] = $plural($diff->d, $lang->get('day'), $lang->get('days'));
			if ($diff->h > 0 && ($format & self::INTERVAL_FORMAT_HOURS)) $parts[] = $plural($diff->h, $lang->get('hour'), $lang->get('hours'));
			if ($diff->i > 0 && ($format & self::INTERVAL_FORMAT_MINUTES)) $parts[] = $plural($diff->i, $lang->get('minute'), $lang->get('minutes'));
			if ($diff->s > 0 && ($format & self::INTERVAL_FORMAT_SECONDS)) $parts[] = $plural($diff->s, $lang->get('second'), $lang->get('seconds'));

			if ($format == self::INTERVAL_FORMAT_AUTO) {
				$str = implode($unitSeparator, array_slice($parts, 0, 2));
			}
			else {
				$str = implode($unitSeparator, $parts);
			}			
		}

		return $str;
	}

	/**
	 * Check if $date is a valid date format
	 *
	 * @param string $date
	 * @return boolean
	 */
	public static function isDate($date)
    {
		$date = str_replace(array('\'', '-', '.', ','), '/', $date);
		$date = explode('/', $date);

		if (count($date) == 1 and is_numeric($date[0])
				and $date[0] < 20991231
				and (checkdate(substr($date[0], 4, 2), substr($date[0], 6, 2), substr($date[0], 0, 4)))) {
			return true;
		}

		if (count($date) == 3
				and is_numeric($date[0])
				and is_numeric($date[1])
				and is_numeric($date[2]) and
				(checkdate($date[0], $date[1], $date[2]) //mmddyyyy
				or checkdate($date[1], $date[0], $date[2]) //ddmmyyyy
				or checkdate($date[1], $date[2], $date[0])) //yyyymmdd
		) {
			return true;
		}

		return false;
    }
}

?>
