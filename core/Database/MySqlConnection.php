<?php

namespace EApp\Database;

use PDO;
use EApp\Database\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use EApp\Database\Query\Grammars\MySqlGrammar as QueryGrammar;

class MySqlConnection extends Connection
{
	/**
	 * GetTrait the default query grammar instance.
	 *
	 * @return \EApp\Database\Query\Grammars\MySqlGrammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new QueryGrammar);
	}

	/**
	 * GetTrait the default post processor instance.
	 *
	 * @return \EApp\Database\Query\Processors\MySqlProcessor
	 */
	protected function getDefaultPostProcessor()
	{
		return new MySqlProcessor;
	}

	/**
	 * GetTrait the Doctrine DBAL driver.
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