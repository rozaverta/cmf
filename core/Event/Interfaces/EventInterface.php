<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace EApp\Event\Interfaces;

interface EventInterface
{
	/**
	 * GetTrait event name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * GetTrait all events parameters
	 *
	 * @return array
	 */
	public function getParams(): array;

	/**
	 * GetTrait event parameter by name
	 *
	 * @param string $name parameter name
	 * @return mixed
	 */
	public function getParam( string $name );

	/**
	 * Prevents the event from being passed to further listeners
	 *
	 * @return mixed
	 */
	public function stopPropagation();

	/**
	 * Checks if stopPropagation has been called
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool;
}