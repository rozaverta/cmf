<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\Database\Events;

use EApp\Database\Connection;

/**
 * Class TransactionEvent
 *
 * @property string $action transaction action name
 *
 * @package EApp\DB\Events
 */
abstract class TransactionEvent extends DatabaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param Connection $connection
	 * @param string $action
	 */
	public function __construct( Connection $connection, string $action )
	{
		parent::__construct($connection, [
			'action' => $action
		]);
	}
}