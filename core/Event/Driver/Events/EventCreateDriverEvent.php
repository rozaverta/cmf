<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 0:32
 */

namespace EApp\Event\Driver\Events;

use EApp\System\Events\SystemDriverEvent;
use EApp\System\Interfaces\SystemDriver;

/**
 * Class EventCreateDriverEvent
 *
 * @property string $event_name
 * @property string $title
 * @property bool $completable
 *
 * @package EApp\Component\Driver\Events
 */
class EventCreateDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriver $driver, string $event_name, string $title, bool $completable )
	{
		parent::__construct( $driver, "create", compact( 'event_name', 'title', 'completable') );
	}
}