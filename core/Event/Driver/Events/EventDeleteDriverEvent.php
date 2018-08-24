<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 0:32
 */

namespace EApp\Event\Driver\Events;

use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

/**
 * Class EventDeleteDriverEvent
 *
 * @property string $event_name
 *
 * @package EApp\Component\Driver\Events
 */
class EventDeleteDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, string $event_name )
	{
		parent::__construct( $driver, "delete", compact( 'event_name' ) );
	}
}