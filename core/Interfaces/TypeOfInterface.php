<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 04.08.2018
 * Time: 0:51
 */

namespace EApp\Interfaces;

interface TypeOfInterface
{
	public function typeOf( & $value, $name = null ) : bool ;
}