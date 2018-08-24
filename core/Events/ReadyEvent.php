<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

/**
 * Class ReadyEvent
 *
 * @property bool $cache
 *
 * @package EApp\Events
 */
class ReadyEvent extends SystemEvent
{
	public function __construct( bool $cache = false )
	{
		parent::__construct(compact('cache'));
	}
}