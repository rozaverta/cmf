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
 * Class QueryExecutedEvent
 * @package EApp\DB\Events
 *
 * @property string $sql The SQL query that was executed.
 * @property array $bindings The array of query bindings.
 * @property float $time The number of milliseconds it took to execute the query.
 */
class QueryExecutedEvent extends DatabaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  \EApp\Database\Connection  $connection
	 * @param  string  $sql
	 * @param  array  $bindings
	 * @param  float  $time
	 */
	public function __construct(Connection $connection, string $sql, array $bindings, float $time)
	{
		parent::__construct($connection, compact($sql, $bindings, $time));
	}
}