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
 * Class DatabaseActionEvent
 *
 * @property \EApp\Database\Connection $connection The database connection instance.
 * @property string $connection_name The name of the connection.
 * @property string $table_name The table name
 * @property string $action The action name
 *
 * @package EApp\DB\Events
 */
abstract class DatabaseActionEvent extends Event
{
	public function __construct( Connection $connection, string $table_name, string $action, array $params = [] )
	{
		$params['connection'] = $connection;
		$params['connection_name'] = $connection->getName();
		$params['table_name'] = $table_name;
		$params['action'] = $action;
		parent::__construct( 'onDatabaseAction', $params );
	}
}