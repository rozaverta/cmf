<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:10
 */

namespace EApp\Route\Interfaces;


interface RuleInterface
{
	public function match( string $value, & $match = null ): bool;
}