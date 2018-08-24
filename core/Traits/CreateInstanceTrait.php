<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 04.08.2018
 * Time: 0:44
 */

namespace EApp\Traits;

trait CreateInstanceTrait
{
	public static function createInstance( ... $args )
	{
		return new static( ... $args );
	}
}