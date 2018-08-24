<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

/**
 * Class RenderGetOnEvent
 *
 * @property string $name
 * @property mixed $value
 *
 * @package EApp\Events
 */
class RenderGetOnEvent extends SystemEvent
{
	public function __construct( string $name, $value )
	{
		parent::__construct(compact('name', 'value'));
		$this->params_allowed[] = "value";
	}
}