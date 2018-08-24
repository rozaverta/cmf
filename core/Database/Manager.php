<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 2:04
 */

namespace EApp\Database;

use EApp\Database\Connectors\ConnectionFactory;
use EApp\Prop;
use EApp\Traits\SingletonInstanceTrait;

/**
 * Class CacheManager
 *
 * @method static Manager getInstance()
 *
 * @package EApp\Database
 */
final class Manager
{
	use SingletonInstanceTrait;

	/**
	 * @var Prop
	 */
	protected $prop;

	/**
	 * @var DatabaseManager
	 */
	protected $manager;

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
	 * GetTrait a connection instance from the global manager.
	 *
	 * @param  string  $connection
	 * @return \EApp\Database\Connection
	 */
	public static function connection($connection = null)
	{
		return static::getInstance()->getConnection($connection);
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
	 * GetTrait the prop instance.
	 *
	 * @return Prop
	 */
	public function getProp()
	{
		return $this->prop;
	}

	public function setAsGlobal()
	{
		self::setInstance($this);
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
	 * GetTrait a registered connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\Connection
	 */
	public function getConnection($name = null)
	{
		return $this->manager->connection($name);
	}

	/**
	 * GetTrait the database manager instance.
	 *
	 * @return \EApp\Database\DatabaseManager
	 */
	public function getDatabaseManager()
	{
		return $this->manager;
	}

	/**
	 * GetTrait a fluent query builder instance.
	 *
	 * @param  string  $table
	 * @param  string  $connection
	 * @return \EApp\Database\Query\Builder
	 */
	public static function table( $table, $connection = null )
	{
		return self::getInstance()->getConnection($connection)->table($table);
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
		return static::getInstance()->getConnection()->$method(...$arguments);
	}
}