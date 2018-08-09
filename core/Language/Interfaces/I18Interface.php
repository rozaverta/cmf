<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 23:01
 */

namespace EApp\Language\Interfaces;

interface I18Interface
{
	public function i18( int $number, array $array ): string;

	public function i18Invoke( int $number, string $name, array $replace = [] ): string;
}