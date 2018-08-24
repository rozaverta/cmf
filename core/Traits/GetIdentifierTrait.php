<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Traits;

trait GetIdentifierTrait
{
	protected $id = 0;

	/**
	 * GetTrait element identifier
	 *
	 * @return mixed
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId( int $id )
	{
		$this->id = $id;
		return $this;
	}
}