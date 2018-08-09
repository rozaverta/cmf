<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2018
 * Time: 17:35
 */

namespace EApp\System\Events;

use EApp\Support\Str;

class SingletonEvent extends SystemEvent
{
	private $ci = [];

	/**
	 * Add new singleton
	 *
	 * @param string|object $name
	 * @param $singleton
	 * @return SingletonEvent
	 */
	public function addSingleton( string $name, $singleton ): self
	{
		$name = Str::studly($name);
		if( isset($this->ci[$name]) )
		{
			throw new \InvalidArgumentException("Duplicated class name '{$name}' for singleton instance object");
		}

		$this->ci[$name] = $singleton;
		return $this;
	}

	/**
	 * Get all singletons
	 * @return array
	 */
	public function getSingletons(): array
	{
		return $this->ci;
	}
}