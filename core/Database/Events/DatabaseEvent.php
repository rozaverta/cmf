<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\Database\Events;

use EApp\Database\Connection;
use EApp\Event\Event;

/**
 * Class DatabaseEvent
 *
 * @property \EApp\Database\Connection $connection The database connection instance.
 * @property string $connection_name The name of the connection.
 *
 * @package EApp\DB\Events
 */
abstract class DatabaseEvent extends Event
{
	public function __construct( Connection $connection, array $params = [] )
	{
		$params['connection'] = $connection;
		$params['connection_name'] = $connection->getName();
		parent::__construct( 'onDatabase', $params );
	}
}