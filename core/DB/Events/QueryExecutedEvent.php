<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\DB\Events;

/**
 * Class QueryExecutedEvent
 * @package EApp\DB\Events
 *
 * @property string $sql The SQL query that was executed.
 * @property array $bindings The array of query bindings.
 * @property float $time The number of milliseconds it took to execute the query.
 * @property \EApp\DB\Connection $connection The database connection instance.
 * @property string $connection_name The database connection name.
 */
class QueryExecutedEvent extends DataBaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  float  $time
	 * @param  \EApp\DB\Connection  $connection
	 */
	public function __construct($sql, $bindings, $time, $connection)
	{
		$connection_name = $connection->getName();
		$this->params = compact($sql, $bindings, $time, $connection, $connection_name);
	}
}