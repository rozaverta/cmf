<?php

namespace EApp\Database\Events;

/**
 * Class StatementPreparedEvent
 *
 * @package EApp\DB\Events
 *
 * @property \PDOStatement $statement The PDO statement.
 */
class StatementPreparedEvent extends DatabaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  \EApp\Database\Connection  $connection
	 * @param  \PDOStatement  $statement
	 */
	public function __construct($connection, $statement)
	{
		parent::__construct($connection, compact('statement'));
	}
}