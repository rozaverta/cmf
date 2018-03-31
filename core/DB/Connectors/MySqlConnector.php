<?php

namespace EApp\DB\Connectors;

use PDO;

class MySqlConnector extends Connector implements ConnectorInterface
{
	/**
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect(array $config)
	{
		$dsn = $this->getDsn($config);
		$options = $this->getOptions($config);

		// We need to grab the PDO options that should be used while making the brand
		// new connection instance. The PDO options control various aspects of the
		// connection's behavior, and some might be specified by the developers.
		$this->pdo = $this->createConnection($dsn, $config, $options);

		$this->configureEncoding($config);

		// Next, we will check to see if a timezone has been specified in this config
		// and if it has we will issue a statement to modify the timezone with the
		// database. Setting this DB timezone is an optional configuration item.
		$this->configureTimezone($config);
		$this->setModes($config);

		return $this->pdo;
	}

	public function select($base)
	{
		if( ! isset($this->pdo) )
		{
			throw new \Exception("The connection to the database is not established");
		}

		$this->pdo->exec("use `{$base}`;");
	}

	public function close()
	{
		if( isset($this->pdo) )
		{
			$this->pdo = null;
		}
	}

	/**
	 * Set the connection character set and collation.
	 *
	 * @param  array  $config
	 * @return void
	 */
	protected function configureEncoding(array $config)
	{
		if( isset($config['charset']))
		{
			$this->pdo->prepare(
				"set names '{$config['charset']}'".$this->getCollation($config)
			)->execute();
		}
	}

	/**
	 * Get the collation for the configuration.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getCollation(array $config)
	{
		return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
	}

	/**
	 * Set the timezone on the connection.
	 *
	 * @param  array  $config
	 * @return void
	 */
	protected function configureTimezone(array $config)
	{
		if (isset($config['timezone'])) {
			$this->pdo->prepare('set time_zone="'.$config['timezone'].'"')->execute();
		}
	}

	/**
	 * Create a DSN string from a configuration.
	 *
	 * Chooses socket or host/port based on the 'unix_socket' config value.
	 *
	 * @param  array   $config
	 * @return string
	 */
	protected function getDsn(array $config)
	{
		return $this->hasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
	}

	/**
	 * Determine if the given configuration array has a UNIX socket value.
	 *
	 * @param  array  $config
	 * @return bool
	 */
	protected function hasSocket(array $config)
	{
		return isset($config['unix_socket']) && ! empty($config['unix_socket']);
	}

	/**
	 * Get the DSN string for a socket configuration.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getSocketDsn(array $config)
	{
		$dsn = "mysql:unix_socket={$config['unix_socket']};";
		if( isset($config['database']) ) $dsn .= "dbname={$config['database']};";
		return $dsn;
	}

	/**
	 * Get the DSN string for a host / port configuration.
	 *
	 * @param  array  $config
	 * @return string
	 */
	protected function getHostDsn(array $config)
	{
		$dsn = "mysql:host=" . (isset($config["host"]) ? $config["host"] : "localhost") . ";";
		if( isset($config["port"]) ) $dsn .= "port=" . intval($config["port"]) . ";";
		if( isset($config["database"]) ) $dsn .= "dbname=" . $config["database"] . ";";
		return $dsn;
	}

	/**
	 * Set the modes for the connection.
	 *
	 * @param  array  $config
	 * @return void
	 */
	protected function setModes(array $config)
	{
		if(isset($config['modes']))
		{
			$this->setCustomModes($config);
		}
		else if(isset($config['strict']))
		{
			if($config['strict'])
			{
				$this->pdo->prepare($this->strictMode())->execute();
			}
			else
			{
				$this->pdo->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
			}
		}
	}

	/**
	 * Set the custom modes on the connection.
	 *
	 * @param  array  $config
	 * @return void
	 */
	protected function setCustomModes(array $config)
	{
		$modes = implode(',', $config['modes']);
		$this->pdo->prepare("set session sql_mode='{$modes}'")->execute();
	}

	/**
	 * Get the query to enable strict mode.
	 *
	 * @return string
	 */
	protected function strictMode()
	{
		return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
	}
}