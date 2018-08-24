<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 11:32
 */

namespace EApp\Traits;

/**
 * Trait CallbackTrait
 *
 * @package EApp\Traits
 */
trait CallbackTrait
{
	protected function callback( \Closure $callback, ... $args )
	{
		return $callback( ... $args );
	}
}