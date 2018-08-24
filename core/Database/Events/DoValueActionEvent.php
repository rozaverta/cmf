<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 07.08.2018
 * Time: 14:10
 */

namespace EApp\Database\Events;

use EApp\Event\Event;

/**
 * Class DoValueActionEvent
 *
 * @property string $action
 *
 * @package EApp\DB\Events
 */
class DoValueActionEvent extends Event
{
	public function __construct( string $action )
	{
		parent::__construct( 'onDoValueAction', compact('action') );
	}
}