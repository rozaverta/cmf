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
	public function i18( $number, array $array );

	public function i18Invoke( $key, $number );
}