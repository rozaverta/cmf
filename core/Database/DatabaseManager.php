<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 19:35
 */

namespace EApp\Database;


use EApp\Database\Connectors\ConnectionFactory;
use EApp\Prop;
use EApp\Support\Arr;
use EApp\Support\Str;
use InvalidArgumentException;
use PDO;

class DatabaseManager implements ConnectionResolverInterface
{
	/**
	 * The application instance.
	 *
	 * @var Prop
	 */
	protected $prop;

	/**
	 * The database connection factory instance.
	 *
	 * @var \EApp\Database\Connectors\ConnectionFactory
	 */
	protected $factory;

	/**
	 * The active connection instances.
	 *
	 * @var array
	 */

	protected $connections = [];
	/**
	 * The custom connection resolvers.
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * Create a new database manager instance.
	 *
	 * @param \EApp\Prop $app
	 * @param \EApp\Database\Connectors\ConnectionFactory $factory
	 */
	public function __construct(Prop $app, ConnectionFactory $factory)
	{
		$this->prop = $app;
		$this->factory = $factory;
	}

	/**
	 * GetTrait a database connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\Connection
	 */
	public function connection($name = null)
	{
		list($database, $type) = $this->parseConnectionName($name);
		$name = $name ?: $database;

		// If we haven't created this connection, we'll create it based on the config
		// provided in the application. Once we've created the connections we will
		// set the "fetch mode" for PDO which determines the query return types.
		if (! isset($this->connections[$name]))
		{
			$this->connections[$name] = $this->configure(
				$this->makeConnection($database), $type
			);
		}

		return $this->connections[$name];
	}

	/**
	 * Parse the connection into an array of the name and read / write type.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function parseConnectionName($name)
	{
		$name = $name ?: $this->getDefaultConnection();
		return Str::endsWith($name, ['::read', '::write']) ? explode('::', $name, 2) : [$name, null];
	}

	/**
	 * Make the database connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\Connection
	 */
	protected function makeConnection($name)
	{
		$config = $this->configuration($name);

		// First we will check by the connection name to see if an extension has been
		// registered specifically for that connection. If it has we will call the
		// Closure and pass it the config allowing it to resolve the connection.
		if (isset($this->extensions[$name]))
		{
			return call_user_func($this->extensions[$name], $config, $name);
		}

		// Next we will check to see if an extension has been registered for a driver
		// and will call the Closure if so, which allows us to have a more generic
		// resolver for the drivers themselves which applies to all connections.
		if (isset($this->extensions[$driver = $config['driver']]))
		{
			return call_user_func($this->extensions[$driver], $config, $name);
		}

		return $this->factory->make($config, $name);
	}

	/**
	 * GetTrait the configuration for a connection.
	 *
	 * @param  string  $name
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function configuration($name)
	{
		$name = $name ?: $this->getDefaultConnection();

		// To get the database connection configuration, we will just pull each of the
		// connection configurations and get the configurations for the given name.
		// If the configuration doesn't exist, we'll throw an exception and bail.
		$connections = $this->prop['database.connections'];
		if (is_null($config = Arr::get($connections, $name)))
		{
			throw new InvalidArgumentException("Database [$name] not configured.");
		}

		return $config;
	}
	/**
	 * Prepare the database connection instance.
	 *
	 * @param  \EApp\Database\Connection $connection
	 * @param  string  $type
	 * @return \EApp\Database\Connection
	 */
	protected function configure(Connection $connection, $type)
	{
		$connection = $this->setPdoForType($connection, $type);

		// First we'll set the fetch mode and a few other dependencies of the database
		// connection. This method basically just configures and prepares it to get
		// used by the application. Once we're finished we'll return it back out.
		if ($this->prop->getIs('events'))
		{
			$connection->setEventDispatcher($this->prop['events']);
		}

		// Here we'll set a reconnector callback. This reconnector can be any callable
		// so we will set a Closure to reconnect from this manager with the name of
		// the connection, which will allow us to reconnect from the connections.
		$connection->setReconnector(function ($connection) {
			$this->reconnect($connection->getName());
		});

		return $connection;
	}

	/**
	 * Prepare the read / write mode for database connection instance.
	 *
	 * @param  \EApp\Database\Connection $connection
	 * @param  string  $type
	 * @return \EApp\Database\Connection
	 */
	protected function setPdoForType(Connection $connection, $type = null)
	{
		if ($type == 'read')
		{
			$connection->setPdo($connection->getReadPdo());
		}
		elseif ($type == 'write')
		{
			$connection->setReadPdo($connection->getPdo());
		}

		return $connection;
	}

	/**
	 * Disconnect from the given database and remove from local cache.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function purge($name = null)
	{
		$name = $name ?: $this->getDefaultConnection();
		$this->disconnect($name);
		unset($this->connections[$name]);
	}

	/**
	 * Disconnect from the given database.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function disconnect($name = null)
	{
		if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()]))
		{
			$this->connections[$name]->disconnect();
		}
	}

	/**
	 * Reconnect to the given database.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\Connection
	 */
	public function reconnect($name = null)
	{
		$this->disconnect($name = $name ?: $this->getDefaultConnection());
		if (! isset($this->connections[$name]))
		{
			return $this->connection($name);
		}
		return $this->refreshPdoConnections($name);
	}

	/**
	 * Refresh the PDO connections on a given connection.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\Connection
	 */
	protected function refreshPdoConnections($name)
	{
		$fresh = $this->makeConnection($name);
		return $this->connections[$name]
			->setPdo($fresh->getPdo())
			->setReadPdo($fresh->getReadPdo());
	}

	/**
	 * GetTrait the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection()
	{
		return $this->prop['database.default'];
	}

	/**
	 * SetTrait the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name)
	{
		$this->prop['database.default'] = $name;
	}

	/**
	 * GetTrait all of the support drivers.
	 *
	 * @return array
	 */
	public function supportedDrivers()
	{
		return ['mysql', 'pgsql'];
	}

	/**
	 * GetTrait all of the drivers that are actually available.
	 *
	 * @return array
	 */
	public function availableDrivers()
	{
		return array_intersect(
			$this->supportedDrivers(),
			str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers())
		);
	}

	/**
	 * Register an extension connection resolver.
	 *
	 * @param  string    $name
	 * @param  callable  $resolver
	 * @return void
	 */
	public function extend($name, callable $resolver)
	{
		$this->extensions[$name] = $resolver;
	}

	/**
	 * Return all of the created connections.
	 *
	 * @return array
	 */
	public function getConnections()
	{
		return $this->connections;
	}

	/**
	 * Dynamically pass methods to the default connection.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->connection()->$method(...$parameters);
	}
}