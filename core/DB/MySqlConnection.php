<?php

namespace EApp\DB;

use PDO;
use EApp\DB\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use EApp\DB\Query\Grammars\MySqlGrammar as QueryGrammar;

class MySqlConnection extends Connection
{
	/**
	 * Get the default query grammar instance.
	 *
	 * @return \EApp\DB\Query\Grammars\MySqlGrammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new QueryGrammar);
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return \EApp\DB\Query\Processors\MySqlProcessor
	 */
	protected function getDefaultPostProcessor()
	{
		return new MySqlProcessor;
	}

	/**
	 * Get the Doctrine DBAL driver.
	 *
	 * @return \Doctrine\DBAL\Driver\PDOMySql\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new DoctrineDriver;
	}

	/**
	 * Bind values to their parameters in the given statement.
	 *
	 * @param  \PDOStatement $statement
	 * @param  array  $bindings
	 * @return void
	 */
	public function bindValues($statement, $bindings)
	{
		foreach ($bindings as $key => $value) {
			$statement->bindValue(
				is_string($key) ? $key : $key + 1, $value,
				is_int($value) || is_float($value) ? PDO::PARAM_INT : PDO::PARAM_STR
			);
		}
	}
}