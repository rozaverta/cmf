<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 4:08
 */

namespace EApp\Database\Connectors;

use PDO;
use Exception;
use Doctrine\DBAL\Driver\PDOConnection;
use EApp\Database\DetectsLostConnectionsTrait;

class Connector
{
	use DetectsLostConnectionsTrait;

	/**
	 * The default PDO connection options.
	 *
	 * @var array
	 */
	protected $options =
		[
			PDO::ATTR_CASE => PDO::CASE_NATURAL,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => false,
			PDO::ATTR_EMULATE_PREPARES => false,
		];

	/**
	 * Create a new PDO connection.
	 *
	 * @param  string  $dsn
	 * @param  array   $config
	 * @param  array   $options
	 * @return \PDO
	 */
	public function createConnection($dsn, array $config, array $options)
	{
		$username = isset($config['username']) ? $config['username'] : null;
		$password = isset($config['password']) ? $config['password'] : null;

		try {
			return $this->createPdoConnection(
				$dsn, $username, $password, $options
			);
		}
		catch (Exception $e) {
			return $this->tryAgainIfCausedByLostConnection(
				$e, $dsn, $username, $password, $options
			);
		}
	}

	/**
	 * Create a new PDO connection instance.
	 *
	 * @param  string  $dsn
	 * @param  string  $username
	 * @param  string  $password
	 * @param  array  $options
	 * @return \PDO
	 */
	protected function createPdoConnection($dsn, $username, $password, $options)
	{
		if(class_exists(PDOConnection::class) && ! $this->isPersistentConnection($options))
		{
			return new PDOConnection($dsn, $username, $password, $options);
		}

		return new PDO($dsn, $username, $password, $options);
	}

	/**
	 * Determine if the connection is persistent.
	 *
	 * @param  array  $options
	 * @return bool
	 */
	protected function isPersistentConnection($options)
	{
		return isset($options[PDO::ATTR_PERSISTENT]) && $options[PDO::ATTR_PERSISTENT];
	}

	/**
	 * Handle an exception that occurred during connect execution.
	 *
	 * @param  \Exception  $e
	 * @param  string  $dsn
	 * @param  string  $username
	 * @param  string  $password
	 * @param  array   $options
	 * @return \PDO
	 *
	 * @throws \Exception
	 */
	protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
	{
		if ($this->causedByLostConnection($e)) {
			return $this->createPdoConnection($dsn, $username, $password, $options);
		}

		throw $e;
	}

	/**
	 * GetTrait the PDO options based on the configuration.
	 *
	 * @param  array  $config
	 * @return array
	 */
	public function getOptions(array $config)
	{
		$options = isset($config['options']) ? $config['options'] : [];
		return array_diff_key($this->options, $options) + $options;
	}

	/**
	 * GetTrait the default PDO connection options.
	 *
	 * @return array
	 */
	public function getDefaultOptions()
	{
		return $this->options;
	}

	/**
	 * SetTrait the default PDO connection options.
	 *
	 * @param  array  $options
	 * @return void
	 */
	public function setDefaultOptions(array $options)
	{
		$this->options = $options;
	}
}