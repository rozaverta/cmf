<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.04.2016
 * Time: 17:46
 */

namespace EApp\Support\Traits;

trait SingletonInstance {

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
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Check instance is loaded
	 *
	 * @return bool
	 */
	public static function hasInstance()
	{
		return self::$init && isset(self::$instance);
	}
}
