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
 * Class EventRenameDriverEvent
 *
 * @property string $event_name
 * @property string $event_name_new
 *
 * @package EApp\Component\Driver\Events
 */
class EventRenameDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriver $driver, string $event_name, string $event_name_new )
	{
		parent::__construct( $driver, "rename", compact( 'event_name', 'event_name_new' ) );
	}
}