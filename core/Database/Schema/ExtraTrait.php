<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2018
 * Time: 21:10
 */

namespace EApp\Database\Schema;


use EApp\Prop;

trait ExtraTrait
{
	/**
	 * @var Prop
	 */
	protected $extra;

	/**
	 * GetTrait extra item
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getExtra(string $name, $default = null)
	{
		return $this->extra->getOr($name, $default);
	}

	/**
	 * @return Prop
	 */
	public function getExtras()
	{
		return $this->extra;
	}

}