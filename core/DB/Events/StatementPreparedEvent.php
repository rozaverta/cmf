<?php

namespace EApp\DB\Events;

/**
 * Class StatementPreparedEvent
 *
 * @package EApp\DB\Events
 *
 * @property \EApp\DB\Connection $connection The database connection instance.
 * @property \PDOStatement $statement The PDO statement.
 */
class StatementPreparedEvent extends DataBaseEvent
{
	/**
	 * Create a new event instance.
	 *
	 * @param  \EApp\DB\Connection  $connection
	 * @param  \PDOStatement  $statement
	 */
	public function __construct($connection, $statement)
	{
		$this->params = compact($connection, $statement);
	}
}