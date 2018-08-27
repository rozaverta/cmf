<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:36
 */

namespace EApp\Support;

class CollectionRecorder extends Collection
{
	protected $limit  = 0;
	protected $offset = 0;
	protected $total  = 0;

	/**
	 * Create a new collection.
	 *
	 * @param mixed $items
	 * @param int $limit
	 * @param int $offset
	 * @param int $total
	 */
	public function __construct($items = [], $limit = 0, $offset = 0, $total = 0 )
	{
		parent::__construct($items);

		$limit = (int) $limit;
		$total = (int) $total;

		if( !$limit )
		{
			$limit = $this->count();
		}

		$this->limit = $limit;
		$this->offset = (int) $offset;
		$this->total = $total < 1 ? $this->count() : $total;
	}

	public function jsonSerialize()
	{
		return
			[
				"total"  => $this->total,
				"limit"  => $this->limit,
				"offset" => $this->offset,
				"items"  => parent::jsonSerialize()
			];
	}

	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return int
	 */
	public function getTotal()
	{
		return $this->total;
	}

	/**
	 * Get current page number.
	 *
	 * @return int
	 */
	public function getPage()
	{
		return $this->offset > $this->limit ? floor($this->offset / $this->limit) : 1;
	}

	/**
	 * Count all pages.
	 *
	 * @return int
	 */
	public function getPages()
	{
		return $this->total > $this->limit ? ceil($this->total / $this->limit) : 1;
	}

	/**
	 * @param array $an_array
	 * @return static
	 */
	public static function __set_state($an_array)
	{
		return new static(
			$an_array["items"] ?? [],
			$an_array["limit"] ?? 0,
			$an_array["offset"] ?? 0,
			$an_array["total"] ?? 0
		);
	}
}