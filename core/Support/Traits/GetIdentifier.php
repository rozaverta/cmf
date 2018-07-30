<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Support\Traits;

trait GetIdentifier
{
	protected $id = 0;

	/**
	 * Get element identifier
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}
}
