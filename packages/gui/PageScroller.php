<?php

//namespace gui;

import('net.URL');

/**
 * This class accepts a paged result set or an array of data and shows a formated page scroller for their navigation.
 * 
 * @package gui
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class PageScroller
{
	const STYLE_NUMBERS = 1;
	const STYLE_LABELS = 2;
	
	/**
	 * Number of pages.
	 * @var int
	 */
	protected $pageCount;
	/**
	 * Current page number.
	 * @var int
	 */
	protected $currentPage;
	/**
	 * URL used for generating the page links.
	 * @var string
	 */
	protected $url;
	
	/**
	* Create a scroller for the specified number of pages.
	* An empty string is returned if page count is not greater than 1 or current page is invalid.
	* 
	* @param int $pageCount Number of pages
	* @param int $currentPage Current page number
	* @param string $url URL that will be used when generating the scroller links. {%PAGE} will be replaced with the page number.
	* @param int $scrollerRange Amount of visible page numbers.
	* @param string	$scrollerStyle CSS class used by the main <div> container. Default is 'page-scroller'.
	* @param boolean $showNextPrev Show legends "Next" and "Previous" instead of ">" and "<" arrows.
	* @return string
	*/
	public static function createScroller($pageCount, $currentPage, $url = null, $scrollerRange = 6, $scrollerStyle = 'page-scroller', $showNextPrev = true)
	{
		$pageCount = intval($pageCount);
		$currentPage = intval($currentPage);
		
		$lang = Lang::getInstance();
		
		$minRange = ($currentPage - $scrollerRange < 1) ? 1 : $currentPage - $scrollerRange;
		$maxRange = ($currentPage + $scrollerRange > $pageCount) ? $pageCount : $currentPage + $scrollerRange;

		if (!$url) {
			$_get = $_GET;
			unset($_get['p']);
			$_qs = URL::toQueryParams($_get);
			$_qs .= empty($_get) ? 'p={%PAGE}' : '&p={%PAGE}';
			$url = URL::fromParams(Request::getInstance()->getParams(), $_qs);
		}
		
		if ($pageCount > 1 && $currentPage <= $pageCount) {
			$out = '<ul class="'.$scrollerStyle.'">';
			if ($currentPage - $scrollerRange > 1) {
				$out .= '<li class="first"><a href="'.str_replace('{%PAGE}', 1, $url).'">«« '.($lang->get("First")).'</a></li>';
			}
			if ($currentPage > 1 && $showNextPrev) {
				$out .= '<li class="previous"><a href="'.str_replace('{%PAGE}', $currentPage-1, $url).'">« '.($lang->get("Previous")).'</a></li>';
			}
			for ($i = $minRange; $i <= $maxRange; $i++) {
				if ($currentPage == $i) $out .='<li><strong><big><a href="'.str_replace('{%PAGE}', $i, $url).'">'.$i.'</a></big></strong></li>';
				else $out .='<li><a href="'.str_replace('{%PAGE}', $i, $url).'">'.$i.'</a></li>';
			}
			if ($currentPage < $pageCount && $showNextPrev) {
				$out .= '<li class="next"><a href="'.str_replace('{%PAGE}', $currentPage+1, $url).'">'.($lang->get("Next")).' »</a></li>';
			}
			if ($currentPage + $scrollerRange < $pageCount) {
				$out .= '<li class="last"><a href="'.str_replace('{%PAGE}', $pageCount, $url).'">'.($lang->get("Last")).' »»</a></li>';
			}
			$out .= '</ul>';
		}
		else{
			$out = '';
		}
		
		return $out;
	}
	
	/**
	 * Constructor.
	 *
	 * @param ResultSet|PagedArray $data A paged result set or array
	 * @param inte $currentPage Current page number
	 * @param string $url URL that will be used when generating the scroller links. {%PAGE} will be replaced with the page number. If NULL, it will be automatically created based on actual URI and the var which contains the page number will be named "p".
	 * @throws InvalidArgumentException if $data is invalid.
	 */
	public function __construct($data, $currentPage = 1, $url = null)
	{
		if ($data instanceof ResultSet || $data instanceof PagedArray) {
			$this->pageCount = $data->getPageCount();
		}
		else {
			throw new InvalidArgumentException("\$data must be an instance of ResultSet or PagedArray.");
		}
		
		$this->currentPage = (int)$currentPage;
		if ($this->currentPage < 1) {
			$this->currentPage = 1;
		}
		$this->setURL($url);
	}
	
	/**
	 * Set base URL to be used as page links.
	 * Page number will be added automatically as a parameter.
	 *
	 * @param string $url
	 * @return void
	 */
	public function setURL($url)
	{
		if (!$url) {
			$_get = $_GET;
			$_get['p'] = '{%PAGE}';
			$this->url = URL::fromParams(Request::getInstance()->getParams(), $_get);
		}
		else {
			$this->url = $url;
		}
	}
	
	/**
	 * Get URL as a string for specified page number.
	 *
	 * @param int $page
	 * @return string
	 */
	public function getURL($page = 0)
	{
		if ($page == 0) $page = $this->currentPage;
		return str_replace('{%PAGE}', $page, $this->url);
	}
	
	/**
	* Get HTML source for this scroller.
	* An empty string is returned if page count is not greater than 1 or current page is invalid.
	* 
	* @param int $scrollerRange Amount of visible page numbers.
	* @param string	$scrollerStyle CSS class used by the main <div> container. Default is 'page-scroller'.
	* @param boolean $showNextPrev Show legends "Next" and "Previous" instead of ">" and "<" arrows.
	* @return string
	*/
	public function getScroller($scrollerRange = 6, $scrollerStyle = 'page-scroller', $showNextPrev = true)
	{
		return self::createScroller($this->pageCount, $this->currentPage, $this->url, $scrollerRange, $scrollerStyle, $showNextPrev);
	}

	/**
	* Get HTML source for this scroller.
	* The anchors points to a valid URL but adds the onclick attribute which calls the specified Javascript function with optional parameters, then returns false.
	* An empty string is returned if page count is not greater than 1 or current page is invalid.
	* When $jsFunction is called, the first parameter is always the page number.
	* 
	* @param string $jsFunction JS function which will be called onclick. It must be a valid function name without parenthesis.
	* @param mixed[] $jsParams An array of PHP values that will be converted to JS values using json_encode() and passed to the JS function.
	* @param int $scrollerRange Amount of visible page numbers.
	* @param string	$scrollerStyle CSS class used by the main <ul> container. Default is 'scroller'.
	* @param boolean $style Set scroller style using class constants.
	* @return string
	*/
	public function getJSScroller($jsFunction, array $jsParams = array(), $scrollerRange = 6, $scrollerStyle = 'scroller', $style = self::STYLE_NUMBERS)
	{			
		$minRange = ($this->currentPage - $scrollerRange < 1) ? 1 : $this->currentPage - $scrollerRange;
		$maxRange = ($this->currentPage + $scrollerRange > $this->pageCount) ? $this->pageCount : $this->currentPage + $scrollerRange;
		$_encParams = array_map('json_encode', $jsParams);
		$sep = count($_encParams) > 0 ? ',' : '';
		$_params = $sep . implode(', ', $_encParams);
		if ($this->pageCount > 1 && $this->currentPage <= $this->pageCount) {
			$out = '<ul class="'.$scrollerStyle.'">';
			if ($this->currentPage - $scrollerRange > 1) {
				$_label = $style == self::STYLE_LABELS ? '«« ' . l("First") : '1';
				$out .= '<li><a href="'.$this->getURL(1).'" onclick=\''.$jsFunction.'(1'.$_params.'); return false;\'>'.$_label.'</a></li>';
			}
			if ($this->currentPage > 1 && $style == self::STYLE_LABELS) {
				$_page = $this->currentPage - 1;
				$out .= '<li><a href="'.$this->getURL($this->currentPage-1).'" onclick=\''.$jsFunction.'('.$_page.$_params.'); return false;\'>« '.l("Previous").'</a></li>';
			}
			for ($i = $minRange; $i <= $maxRange; $i++) {
				$_class = ($this->currentPage == $i) ? ' class="selected"' : '';
				$out .='<li'.$_class.'><a href="'.$this->getURL($i).'" onclick=\''.$jsFunction.'('.$i.$_params.'); return false;\'>'.$i.'</a></li>';
			}
			if ($this->currentPage < $this->pageCount && $style == self::STYLE_LABELS) {
				$_page = $this->currentPage + 1;
				$out .= '<li><a href="'.$this->getURL($this->currentPage+1).'" onclick=\''.$jsFunction.'('.$_page.$_params.'); return false;\'>'.l("Next").' »</a></li>';
			}
			if ($this->currentPage + $scrollerRange < $this->pageCount) {
				$_page = $this->pageCount;
				$_label = $style == self::STYLE_LABELS ? l("Last") . ' »»' : $this->pageCount;
				$out .= '<li><a href="'.$this->getURL((int)$this->pageCount).'" onclick=\''.$jsFunction.'('.$_page.$_params.'); return false;\'>'.$_label.'</a></li>';
			}
			$out .= '</ul>';
		}
		else{
			$out = '';
		}
		return $out;
	}
	
	/**
	 * Returns the HTML source of this page scroller.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getScroller();
	}
}
?>
