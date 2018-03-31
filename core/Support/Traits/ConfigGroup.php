<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Support\Traits;

use EApp\App;

trait ConfigGroup
{
	/**
	 * @var \EApp\Prop
	 */
	protected $conf;

	protected function _configLoad( $group, $attachment = null )
	{
		$conf = App::getInstance()->Config;
		$this->conf = $conf->group( $group );

		if( is_array( $attachment ) ) {
			foreach( $attachment as $name ) {
				if( !isset($this->conf[$name]) ) {
					$this->conf[$name] = $conf->get($name);
				}
			}
		}
	}

	/**
	 * @param $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function _configGet( $name, $default = false )
	{
		return isset($this->conf) ? $this->conf->getOr($name, $default) : $default;
	}
}
