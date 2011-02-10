<?php

//namespace util;

/**
 * Class that supports iterating through a paged array.
 *
 * @package util
 * @author Demián Andrés Rodriguez (demian85@gmail.com)
 */
class PagedArray implements Iterator
{
	/**
	 * @var mixed[]
	 */
	protected $data;
	/**
	 * @var int
	 */
	protected $actualPage;
	/**
	 * @var int
	 */
	protected $pageCount;
	
	/**
	 * Creates a paged array. You can retrieve page numbers using the PageScroller class.
	 *
	 * @param array $data
	 * @param int $rowsPerPage Must be an int greater than 0
	 * @param string|int $pageId Name of the requested parameter which contains the actual page, or the actual page itself as an integer.
	 * @throws InvalidArgumentException if $rowsPerPage is not greater than 0
	 */
	public function __construct(array $data, $rowsPerPage = 10, $pageId = 'p')
	{
		if ($rowsPerPage < 1) {
			throw new InvalidArgumentException("Rows per page must be an integer greater than 0");
		}
		
		$request = Request::getInstance();
		if (is_string($pageId)) {
			$actualPage = $request->getParam($pageId, 1);
		}
		else {
			$actualPage = (int)$pageId;
		}
		
		if ($actualPage < 1) $actualPage = 1;
		$this->actualPage = $actualPage;
		$this->pageCount = ceil(count($data) / $rowsPerPage);
		$recStart = $actualPage * $rowsPerPage - $rowsPerPage;
		$recEnd = $rowsPerPage;
		
		$this->data = array_slice($data, $recStart, $recEnd, true);
	}
	/**
	 * Get data array
	 *
	 * @return mixed[]
	 */
	public function getData()
	{
		return $this->data;
	}
	/**
	 * Get current page
	 *
	 * @return int
	 */
	public function getPage()
	{
		return $this->actualPage;
	}
	/**
	 * Get page count
	 *
	 * @return int
	 */
	public function getPageCount()
	{
		return $this->pageCount;
	}

	public function current()
	{
		return current($this->data);
	}
	public function key()
	{
		return key($this->data);
	}
	public function next()
	{
		next($this->data);
	}
	public function rewind()
	{
		reset($this->data);
	}
	public function valid()
	{
		return current($this->data) !== false;
	}
}

?>
