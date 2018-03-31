<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\DB\Events;

/**
 * Class ConnectionEvent
 *
 * @package EApp\DB\Events
 *
 * @property \EApp\DB\Connection $connection The database connection instance.
 * @property string $connection_name The name of the connection.
 */
abstract class ConnectionEvent extends DataBaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  \EApp\DB\Connection  $connection
	 */
	public function __construct($connection)
	{
		$this->params['connection'] = $connection;
		$this->params['connection_name'] = $connection->getName();
	}
}