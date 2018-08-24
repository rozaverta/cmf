<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.04.2016
 * Time: 17:46
 */

namespace EApp\Traits;

trait SingletonInstanceTrait
{
	protected function __clone() {}
	protected function __construct() {}

	private static $instance;
	private static $init = false;

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if( !self::$init )
		{
			self::$init = true;
			self::setInstance(new self());
		}

		return self::$instance;
	}

	/**
	 * Check instance is loaded
	 *
	 * @return bool
	 */
	public static function hasInstance(): bool
	{
		return self::$init && isset(self::$instance);
	}

	protected static function setInstance( $instance )
	{
		self::$instance = $instance;
	}
}