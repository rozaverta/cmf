<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace EApp\Event\Interfaces;

use EApp\Event\Dispatcher;

interface EventPrepareInterface
{
	/**
	 * Constructor
	 *
	 * @param string $name event name
	 */
	public function __construct( string $name );

	/**
	 * GetTrait event parameter by name
	 *
	 * @param Dispatcher $manager
	 * @return mixed
	 */
	public function prepare( Dispatcher $manager );
}