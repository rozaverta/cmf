<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 2:04
 */

namespace EApp\DB;

use EApp\DB\Connectors\ConnectionFactory;
use EApp\Prop;

final class Manager
{
	/**
	 * @var Prop
	 */
	protected $prop;

	protected $manager;

	private static $instance;
	private function __clone() {}

	protected function __construct( Prop $prop = null )
	{
		if( is_null($prop) )
		{
			$prop = new Prop([
				'database.connections' => Prop::cache("db")
			]);
		}

		// add container
		$this->prop = $prop;

		// Once we have the container setup, we will setup the default configuration
		// options in the container "config" binding. This will make the database
		// manager work correctly out of the box without extreme configuration.
		$this->prop['database.default'] = 'default';

		$this->manager = new DatabaseManager($this->prop, new ConnectionFactory($this->prop));
	}

	/**
	 * Database default instance is load.
	 *
	 * @return bool
	 */
	public static function loaded()
	{
		return isset(self::$instance);
	}

	/**
	 * @return self
	 */
	protected static function manager()
	{
		if( ! isset(self::$instance) )
		{
			self::createManager()->setAsGlobal();
		}

		return self::$instance;
	}

	/**
	 * Get a connection instance from the global manager.
	 *
	 * @param  string  $connection
	 * @return \EApp\DB\Connection
	 */
	public static function connection($connection = null)
	{
		return static::manager()->getConnection($connection);
	}

	/**
	 * Create new manager instance.
	 *
	 * @param Prop|null $properties
	 * @return Manager
	 */
	public static function createManager( Prop $properties = null )
	{
		$self = new self($properties);
		return $self;
	}

	/**
	 * Get the prop instance.
	 *
	 * @return Prop
	 */
	public function getProp()
	{
		return $this->prop;
	}

	public function setAsGlobal()
	{
		self::$instance = $this;
		return $this;
	}

	/**
	 * Register a connection with the manager.
	 *
	 * @param  array   $config
	 * @param  string  $name
	 * @return void
	 */
	public function addConnection(array $config, $name = 'default')
	{
		$connections = $this->prop['database.connections'];
		$connections[$name] = $config;
		$this->prop['database.connections'] = $connections;
	}

	/**
	 * Get a registered connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\DB\Connection
	 */
	public function getConnection($name = null)
	{
		return $this->manager->connection($name);
	}

	/**
	 * Get the database manager instance.
	 *
	 * @return \EApp\DB\DatabaseManager
	 */
	public function getDatabaseManager()
	{
		return $this->manager;
	}

	/**
	 * Get a fluent query builder instance.
	 *
	 * @param  string  $table
	 * @param  string  $connection
	 * @return \EApp\DB\Query\Builder
	 */
	public static function table( $table, $connection = null )
	{
		return self::manager()->getConnection($connection)->table($table);
	}

	/**
	 * Dynamically pass methods to the default connection.
	 *
	 * @param  string  $method
	 * @param  array   $arguments
	 * @return mixed
	 */
	public static function __callStatic( $method, $arguments )
	{
		return static::manager()->getConnection()->$method(...$arguments);
	}
}