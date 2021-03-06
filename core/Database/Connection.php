<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 2:04
 */

namespace EApp\Database;

use EApp\Event\Dispatcher;
use PDO;
use Closure;
use Exception;
use PDOStatement;
use LogicException;
use DateTimeInterface;
use EApp\Database\Query\Expression;
use EApp\Database\Query\Processors\Processor;
use EApp\Support\Arr;
use EApp\Database\Query\Builder as QueryBuilder;
use EApp\Database\Query\Grammars\Grammar as QueryGrammar;

class Connection implements ConnectionInterface
{
	use DetectsDeadlocksTrait;
	use DetectsLostConnectionsTrait;
	use Concerns\ManagesTransactions;

	const FETCH_ASSOC   = PDO::FETCH_ASSOC;
	const FETCH_OBJ     = PDO::FETCH_OBJ;
	const FETCH_BOUND   = PDO::FETCH_BOUND;
	const FETCH_COLUMN  = PDO::FETCH_COLUMN;
	const FETCH_NUM     = PDO::FETCH_NUM;

	/**
	 * The active PDO connection.
	 *
	 * @var \PDO|\Closure
	 */
	protected $pdo;

	/**
	 * The active PDO connection used for reads.
	 *
	 * @var \PDO|\Closure
	 */
	protected $readPdo;

	/**
	 * The name of the connected database.
	 *
	 * @var string
	 */
	protected $database;

	/**
	 * The table prefix for the connection.
	 *
	 * @var string
	 */
	protected $tablePrefix = '';

	/**
	 * The database connection configuration options.
	 *
	 * @var array
	 */
	protected $config = [ ];

	/**
	 * The reconnector instance for the connection.
	 *
	 * @var callable
	 */
	protected $reconnector;

	/**
	 * The query grammar implementation.
	 *
	 * @var \EApp\Database\Query\Grammars\Grammar
	 */
	protected $queryGrammar;

	/**
	 * The query post processor implementation.
	 *
	 * @var \EApp\Database\Query\Processors\Processor
	 */
	protected $postProcessor;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \EApp\Event\Dispatcher
	 */
	protected $events;

	/**
	 * The default fetch mode of the connection.
	 *
	 * @var int
	 */
	protected $fetchMode = PDO::FETCH_OBJ;

	/**
	 * The number of active transactions.
	 *
	 * @var int
	 */
	protected $transactions = 0;

	/**
	 * Indicates if changes have been made to the database.
	 *
	 * @var int
	 */
	protected $recordsModified = false;

	/**
	 * All of the queries run against the connection.
	 *
	 * @var array
	 */
	protected $queryLog = [ ];

	/**
	 * Indicates whether queries are being logged.
	 *
	 * @var bool
	 */
	protected $loggingQueries = false;

	/**
	 * Indicates if the connection is in a "dry run".
	 *
	 * @var bool
	 */
	protected $pretending = false;

	protected $timing = 0;

	protected $queries = 0;

	/**
	 * The connection resolvers.
	 *
	 * @var array
	 */
	protected static $resolvers = [ ];

	/**
	 * Create a new database connection instance.
	 *
	 * @param  \PDO|\Closure $pdo
	 * @param  string $database
	 * @param  string $tablePrefix
	 * @param  array $config
	 */
	public function __construct( $pdo, $database = '', $tablePrefix = '', array $config = [ ] )
	{
		$this->pdo = $pdo;

		// First we will setup the default properties. We keep track of the DB
		// name we are connected to since it is needed when some reflective
		// type commands are run such as checking whether a table exists.
		$this->database = $database;
		$this->tablePrefix = $tablePrefix;
		$this->config = $config;

		// We need to initialize a query grammar and the query post processors
		// which are both very important parts of the database abstractions
		// so we initialize these to their default values while starting.
		$this->useDefaultQueryGrammar();
		$this->useDefaultPostProcessor();
	}

	/**
	 * Set the query grammar to the default implementation.
	 *
	 * @return void
	 */
	public function useDefaultQueryGrammar()
	{
		$this->queryGrammar = $this->getDefaultQueryGrammar();
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return \EApp\Database\Query\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return new QueryGrammar;
	}

	/**
	 * Set the query post processor to the default implementation.
	 *
	 * @return void
	 */
	public function useDefaultPostProcessor()
	{
		$this->postProcessor = $this->getDefaultPostProcessor();
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return \EApp\Database\Query\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new Processor;
	}

	/**
	 * Begin a fluent query against a database table.
	 *
	 * @param  string $table
	 * @return \EApp\Database\Query\Builder
	 */
	public function table( $table )
	{
		return $this->query()->from( $table );
	}

	/**
	 * Get a new query builder instance.
	 *
	 * @return \EApp\Database\Query\Builder
	 */
	public function query()
	{
		return new QueryBuilder(
			$this, $this->getQueryGrammar(), $this->getPostProcessor()
		);
	}

	/**
	 * Run a select statement and return a single result.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  bool $useReadPdo
	 * @param  null | string $resultClass
	 * @return mixed
	 */
	public function selectOne( $query, $bindings = [ ], $useReadPdo = true, $resultClass = null )
	{
		$records = $this->select( $query, $bindings, $useReadPdo, $resultClass );
		return array_shift( $records );
	}

	/**
	 * Run a select statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  null | string $resultClass
	 * @return array
	 */
	public function selectFromWriteConnection( $query, $bindings = [ ], $resultClass = null )
	{
		return $this->select( $query, $bindings, false, $resultClass );
	}

	/**
	 * Run a select statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  bool $useReadPdo
	 * @param  null | string $resultClass
	 * @return array
	 */
	public function select( $query, $bindings = [ ], $useReadPdo = true, $resultClass = null )
	{
		$fetchCustomClass = ! is_null($resultClass) && class_exists($resultClass);

		return $this->run( $query, $bindings, function ( $query, $bindings ) use ( $useReadPdo, $fetchCustomClass, $resultClass ) {
			if( $this->pretending() )
			{
				return [];
			}

			// For select statements, we'll simply execute the query and return an array
			// of the database result set. Each element in the array will be a single
			// row from the database table, and will either be an array or objects.
			$statement = $this->prepared( $this->getPdoForSelect( $useReadPdo )->prepare( $query ), ! $fetchCustomClass );
			$this->bindValues( $statement, $this->prepareBindings( $bindings ) );
			$statement->execute();

			return $fetchCustomClass ? $statement->fetchAll(\PDO::FETCH_CLASS, $resultClass) : $statement->fetchAll();
		});
	}

	/**
	 * Run a select statement against the database and returns a generator.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  bool $useReadPdo
	 * @param  null | string $resultClass
	 * @return \Generator
	 */
	public function cursor( $query, $bindings = [ ], $useReadPdo = true, $resultClass = null )
	{
		$fetchCustomClass = ! is_null($resultClass) && class_exists($resultClass);

		$statement = $this->run( $query, $bindings, function ( $query, $bindings ) use ( $useReadPdo, $fetchCustomClass ) {
			if( $this->pretending() )
			{
				return [ ];
			}

			// First we will create a statement for the query. Then, we will set the fetch
			// mode and prepare the bindings for the query. Once that's done we will be
			// ready to execute the query against the database and return the cursor.
			$statement = $this->prepared( $this->getPdoForSelect( $useReadPdo )->prepare( $query ), ! $fetchCustomClass );
			$this->bindValues(
				$statement, $this->prepareBindings( $bindings )
			);

			// Next, we'll execute the query against the database and return the statement
			// so we can return the cursor. The cursor will use a PHP generator to give
			// back one row at a time without using a bunch of memory to render them.

			$statement->execute();
			return $statement;
		});

		if( $fetchCustomClass )
		{
			while( $record = $statement->fetchObject( $resultClass ) )
			{
				yield $record;
			}
		}
		else
		{
			while( $record = $statement->fetch() )
			{
				yield $record;
			}
		}
	}

	/**
	 * Configure the PDO prepared statement.
	 *
	 * @param \PDOStatement $statement
	 * @param bool $fetchMode
	 * @return PDOStatement
	 */
	protected function prepared( PDOStatement $statement, $fetchMode = true )
	{
		if( $fetchMode )
		{
			$statement->setFetchMode( $this->fetchMode );
		}
		if( isset( $this->events ) )
		{
			$this->events->dispatch( new Events\StatementPreparedEvent( $this, $statement ) );
		}
		return $statement;
	}

	/**
	 * Get the PDO connection to use for a select query.
	 *
	 * @param  bool $useReadPdo
	 * @return \PDO
	 */
	protected function getPdoForSelect( $useReadPdo = true )
	{
		return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
	}

	/**
	 * Run an insert statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @return bool
	 */
	public function insert( $query, $bindings = [ ] )
	{
		return $this->statement( $query, $bindings );
	}

	/**
	 * Run an update statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @return int
	 */
	public function update( $query, $bindings = [ ] )
	{
		return $this->affectingStatement( $query, $bindings );
	}

	/**
	 * Run a delete statement against the database.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @return int
	 */
	public function delete( $query, $bindings = [ ] )
	{
		return $this->affectingStatement( $query, $bindings );
	}

	/**
	 * Execute an SQL statement and return the boolean result.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @return bool
	 */
	public function statement( $query, $bindings = [ ] )
	{
		return $this->run( $query, $bindings, function ( $query, $bindings ) {
			if( $this->pretending() )
			{
				return true;
			}

			$statement = $this->getPdo()->prepare( $query );
			$this->bindValues( $statement, $this->prepareBindings( $bindings ) );
			$this->recordsHaveBeenModified();

			return $statement->execute();
		} );
	}

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @return int
	 */
	public function affectingStatement( $query, $bindings = [ ] )
	{
		return $this->run( $query, $bindings, function ( $query, $bindings ) {
			if( $this->pretending() )
			{
				return 0;
			}

			// For update or delete statements, we want to get the number of rows affected
			// by the statement and return that back to the developer. We'll first need
			// to execute the statement and then we'll use PDO to fetch the affected.
			$statement = $this->getPdo()->prepare( $query );
			$this->bindValues( $statement, $this->prepareBindings( $bindings ) );
			$statement->execute();
			$this->recordsHaveBeenModified(
				( $count = $statement->rowCount() ) > 0
			);

			return $count;
		} );
	}

	/**
	 * Run a raw, unprepared query against the PDO connection.
	 *
	 * @param  string $query
	 * @return bool
	 */
	public function unprepared( $query )
	{
		return $this->run( $query, [], function ( $query ) {
			if( $this->pretending() )
			{
				return true;
			}

			$this->recordsHaveBeenModified(
				$change = ( $this->getPdo()->exec( $query ) === false ? false : true )
			);

			return $change;
		});
	}

	/**
	 * Execute the given callback in "dry run" mode.
	 *
	 * @param  \Closure $callback
	 * @return array
	 */
	public function pretend( Closure $callback )
	{
		return $this->withFreshQueryLog( function () use ( $callback ) {

			$this->pretending = true;

			// Basically to make the database connection "pretend", we will just return
			// the default values for all the query methods, then we will return an
			// array of queries that were "executed" within the Closure callback.
			$callback( $this );
			$this->pretending = false;

			return $this->queryLog;
		} );
	}

	/**
	 * Execute the given callback in "dry run" mode.
	 *
	 * @param  \Closure $callback
	 * @return array
	 */
	protected function withFreshQueryLog( $callback )
	{
		$loggingQueries = $this->loggingQueries;

		// First we will back up the value of the logging queries property and then
		// we'll be ready to run callbacks. This query log will also get cleared
		// so we will have a new log of all the queries that are executed now.
		$this->enableQueryLog();
		$this->queryLog = [ ];

		// Now we'll execute this callback and capture the result. Once it has been
		// executed we will restore the value of query logging and give back the
		// value of hte callback so the original callers can have the results.
		$result = $callback();
		$this->loggingQueries = $loggingQueries;
		return $result;
	}

	/**
	 * Bind values to their parameters in the given statement.
	 *
	 * @param  \PDOStatement $statement
	 * @param  array $bindings
	 * @return void
	 */
	public function bindValues( $statement, $bindings )
	{
		foreach( $bindings as $key => $value ) {
			$statement->bindValue(
				is_string( $key ) ? $key : $key + 1, $value,
				is_int( $value ) ? PDO::PARAM_INT : PDO::PARAM_STR
			);
		}
	}

	/**
	 * Prepare the query bindings for execution.
	 *
	 * @param  array $bindings
	 * @return array
	 */
	public function prepareBindings( array $bindings )
	{
		$grammar = $this->getQueryGrammar();

		foreach( $bindings as $key => $value )
		{
			// We need to transform all instances of DateTimeInterface into the actual
			// date string. Each query grammar maintains its own date string format
			// so we'll just ask the grammar for the format to get from the date.
			if( $value instanceof DateTimeInterface )
			{
				$bindings[ $key ] = $value->format( $grammar->getDateTimeFormatString() );
			}
			else if( $value === false )
			{
				$bindings[ $key ] = 0;
			}
		}

		return $bindings;
	}

	/**
	 * Run a SQL statement and log its execution context.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  \Closure $callback
	 * @return mixed
	 *
	 * @throws \EApp\Database\QueryException
	 */
	protected function run( $query, $bindings, Closure $callback )
	{
		$this->reconnectIfMissingConnection();
		$start = microtime( true );

		// Here we will run this query. If an exception occurs we'll determine if it was
		// caused by a connection that has been lost. If that is the cause, we'll try
		// to re-establish connection and re-run the query with a fresh connection.
		try {
			$result = $this->runQueryCallback( $query, $bindings, $callback );
		}
		catch( QueryException $e ) {
			$result = $this->handleQueryException(
				$e, $query, $bindings, $callback
			);
		}

		// Once we have run the query we will calculate the time that it took to run and
		// then log the query, bindings, and execution time so we will report them on
		// the event that the developer needs them. We'll log time in milliseconds.
		$this->logQuery(
			$query, $bindings, $this->getElapsedTime( $start )
		);

		return $result;
	}

	/**
	 * Run a SQL statement.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  \Closure $callback
	 * @return mixed
	 *
	 * @throws \EApp\Database\QueryException
	 */
	protected function runQueryCallback( $query, $bindings, Closure $callback )
	{
		// To execute the statement, we'll simply call the callback, which will actually
		// run the SQL against the PDO connection. Then we can calculate the time it
		// took to execute and log the query SQL, bindings and time in our memory.
		try {
			$result = $callback( $query, $bindings );
		}

		// If an exception occurs when attempting to run a query, we'll format the error
		// message to include the bindings with SQL, which will make this exception a
		// lot more helpful to the developer instead of just the database's errors.
		catch( Exception $e ) {
			throw new QueryException(
				$query, $this->prepareBindings( $bindings ), $e
			);
		}

		return $result;
	}

	/**
	 * Log a query in the connection's query log.
	 *
	 * @param  string $query
	 * @param  array $bindings
	 * @param  float|null $time
	 * @return void
	 */
	public function logQuery( string $query, array $bindings, $time = null )
	{
		$this->queries ++;
		if( $time )
		{
			$this->timing += $time;
		}
		if( isset( $this->events ) )
		{
			$this->events->dispatch( new Events\QueryExecutedEvent( $this, $query, $bindings, $time ?? 0 ) );
		}
		if( $this->loggingQueries )
		{
			$this->queryLog[] = compact( 'query', 'bindings', 'time' );
		}
	}

	public function getTiming()
	{
		return $this->timing;
	}

	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Get the elapsed time since a given starting point.
	 *
	 * @param  int $start
	 * @return float
	 */
	protected function getElapsedTime( $start )
	{
		return round( ( microtime( true ) - $start ) * 1000, 2 );
	}

	/**
	 * Handle a query exception.
	 *
	 * @param  \Exception $e
	 * @param  string $query
	 * @param  array $bindings
	 * @param  \Closure $callback
	 * @return mixed
	 * @throws \Exception
	 */
	protected function handleQueryException( $e, $query, $bindings, Closure $callback )
	{
		if( $this->transactions >= 1 )
		{
			throw $e;
		}
		return $this->tryAgainIfCausedByLostConnection(
			$e, $query, $bindings, $callback
		);
	}

	/**
	 * Handle a query exception that occurred during query execution.
	 *
	 * @param  \EApp\Database\QueryException $e
	 * @param  string $query
	 * @param  array $bindings
	 * @param  \Closure $callback
	 * @return mixed
	 *
	 * @throws \EApp\Database\QueryException
	 */
	protected function tryAgainIfCausedByLostConnection( QueryException $e, $query, $bindings, Closure $callback )
	{
		if( $this->causedByLostConnection( $e->getPrevious() ) )
		{
			$this->reconnect();
			return $this->runQueryCallback( $query, $bindings, $callback );
		}
		throw $e;
	}

	/**
	 * Reconnect to the database.
	 *
	 * @return void
	 *
	 * @throws \LogicException
	 */
	public function reconnect()
	{
		if( is_callable( $this->reconnector ) )
		{
			return call_user_func( $this->reconnector, $this );
		}
		throw new LogicException( 'Lost connection and no reconnector available.' );
	}

	/**
	 * Reconnect to the database if a PDO connection is missing.
	 *
	 * @return void
	 */
	protected function reconnectIfMissingConnection()
	{
		if( is_null( $this->pdo ) )
		{
			$this->reconnect();
		}
	}

	/**
	 * Disconnect from the underlying PDO connection.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		$this->setPdo( null )->setReadPdo( null );
	}

	/**
	 * Register a database query listener with the connection.
	 *
	 * @param  \Closure $callback
	 * @return void
	 */
	public function listen( Closure $callback )
	{
		if( ! isset($this->events) )
		{
			$this->events = new Dispatcher('onDataBase');
		}
		$this->events->listen( $callback );
	}

	/**
	 * Fire an event for this connection.
	 *
	 * @param  string $event
	 * @return array|null
	 */
	protected function fireConnectionEvent( $event )
	{
		if( isset( $this->events ) )
		{
			switch( $event ) {
				case 'beganTransaction': $this->events->dispatch( new Events\TransactionBeginningEvent( $this ) ); break;
				case 'committed': $this->events->dispatch( new Events\TransactionCommittedEvent( $this ) ); break;
				case 'rollingBack': $this->events->dispatch( new Events\TransactionRolledBackEvent( $this ) ); break;
			}
		}
	}

	/**
	 * Get a new raw query expression.
	 *
	 * @param  mixed $value
	 * @return \EApp\Database\Query\Expression
	 */
	public function raw( $value )
	{
		return new Expression( $value );
	}

	/**
	 * Indicate if any records have been modified.
	 *
	 * @param  bool $value
	 * @return void
	 */
	public function recordsHaveBeenModified( $value = true )
	{
		if( !$this->recordsModified )
		{
			$this->recordsModified = $value;
		}
	}

	/**
	 * Get the current PDO connection.
	 *
	 * @return \PDO
	 */
	public function getPdo()
	{
		if( $this->pdo instanceof Closure )
		{
			return $this->pdo = call_user_func( $this->pdo );
		}
		return $this->pdo;
	}

	/**
	 * Get the current PDO connection used for reading.
	 *
	 * @return \PDO
	 */
	public function getReadPdo()
	{
		if( $this->transactions > 0 )
		{
			return $this->getPdo();
		}
		if( $this->getConfig('sticky') && $this->recordsModified )
		{
			return $this->getPdo();
		}
		if( $this->readPdo instanceof Closure )
		{
			return $this->readPdo = call_user_func( $this->readPdo );
		}
		return $this->readPdo ?: $this->getPdo();
	}

	/**
	 * Set the PDO connection.
	 *
	 * @param  \PDO|\Closure|null $pdo
	 * @return $this
	 */
	public function setPdo( $pdo )
	{
		$this->transactions = 0;
		$this->pdo = $pdo;
		return $this;
	}

	/**
	 * Set the PDO connection used for reading.
	 *
	 * @param  \PDO||\Closure|null  $pdo
	 * @return $this
	 */
	public function setReadPdo( $pdo )
	{
		$this->readPdo = $pdo;
		return $this;
	}

	/**
	 * Set the reconnect instance on the connection.
	 *
	 * @param  callable $reconnector
	 * @return $this
	 */
	public function setReconnector( callable $reconnector )
	{
		$this->reconnector = $reconnector;
		return $this;
	}

	/**
	 * Get the database connection name.
	 *
	 * @return string|null
	 */
	public function getName()
	{
		return $this->getConfig( 'name' );
	}

	/**
	 * Get an option from the configuration options.
	 *
	 * @param  string|null $option
	 * @return mixed
	 */
	public function getConfig( $option = null )
	{
		return Arr::get( $this->config, $option );
	}

	/**
	 * Get the PDO driver name.
	 *
	 * @return string
	 */
	public function getDriverName()
	{
		return $this->getConfig( 'driver' );
	}

	/**
	 * Get the query grammar used by the connection.
	 *
	 * @return \EApp\Database\Query\Grammars\Grammar
	 */
	public function getQueryGrammar()
	{
		return $this->queryGrammar;
	}

	/**
	 * Set the query grammar used by the connection.
	 *
	 * @param  \EApp\Database\Query\Grammars\Grammar $grammar
	 * @return void
	 */
	public function setQueryGrammar( Query\Grammars\Grammar $grammar )
	{
		$this->queryGrammar = $grammar;
	}

	/**
	 * Get the query post processor used by the connection.
	 *
	 * @return \EApp\Database\Query\Processors\Processor
	 */
	public function getPostProcessor()
	{
		return $this->postProcessor;
	}

	/**
	 * Set the query post processor used by the connection.
	 *
	 * @param  \EApp\Database\Query\Processors\Processor $processor
	 * @return void
	 */
	public function setPostProcessor( Processor $processor )
	{
		$this->postProcessor = $processor;
	}

	/**
	 * Get the event dispatcher used by the connection.
	 *
	 * @return \EApp\Event\Dispatcher
	 */
	public function getEventDispatcher()
	{
		return $this->events;
	}

	/**
	 * Set the event dispatcher instance on the connection.
	 *
	 * @param  \EApp\Event\Dispatcher $events
	 * @return void
	 */
	public function setEventDispatcher( Dispatcher $events )
	{
		$this->events = $events;
	}

	/**
	 * Determine if the connection in a "dry run".
	 *
	 * @return bool
	 */
	public function pretending()
	{
		return $this->pretending === true;
	}

	/**
	 * Get the connection query log.
	 *
	 * @return array
	 */
	public function getQueryLog()
	{
		return $this->queryLog;
	}

	/**
	 * Clear the query log.
	 *
	 * @return void
	 */
	public function flushQueryLog()
	{
		$this->queryLog = [];
	}

	/**
	 * Enable the query log on the connection.
	 *
	 * @return void
	 */
	public function enableQueryLog()
	{
		$this->loggingQueries = true;
	}

	/**
	 * Disable the query log on the connection.
	 *
	 * @return void
	 */
	public function disableQueryLog()
	{
		$this->loggingQueries = false;
	}

	/**
	 * Determine whether we're logging queries.
	 *
	 * @return bool
	 */
	public function logging()
	{
		return $this->loggingQueries;
	}

	/**
	 * Get the name of the connected database.
	 *
	 * @return string
	 */
	public function getDatabaseName()
	{
		return $this->database;
	}

	/**
	 * Set the name of the connected database.
	 *
	 * @param  string $database
	 * @return void
	 */
	public function setDatabaseName( $database )
	{
		$this->database = $database;
	}

	/**
	 * Get the table prefix for the connection.
	 *
	 * @return string
	 */
	public function getTablePrefix()
	{
		return $this->tablePrefix;
	}

	/**
	 * Set the table prefix in use by the connection.
	 *
	 * @param  string $prefix
	 * @return void
	 */
	public function setTablePrefix( $prefix )
	{
		$this->tablePrefix = $prefix;
		$this->getQueryGrammar()->setTablePrefix( $prefix );
	}

	/**
	 * Set the table prefix and return the grammar.
	 *
	 * @param  \EApp\Database\Grammar $grammar
	 * @return \EApp\Database\Grammar
	 */
	public function withTablePrefix( Grammar $grammar )
	{
		$grammar->setTablePrefix( $this->tablePrefix );
		return $grammar;
	}

	/**
	 * Register a connection resolver.
	 *
	 * @param  string $driver
	 * @param  \Closure $callback
	 * @return void
	 */
	public static function resolverFor( $driver, Closure $callback )
	{
		static::$resolvers[ $driver ] = $callback;
	}

	/**
	 * Get the connection resolver for the given driver.
	 *
	 * @param  string $driver
	 * @return mixed
	 */
	public static function getResolver( $driver )
	{
		return isset(static::$resolvers[ $driver ]) ? static::$resolvers[ $driver ] : null;
	}
}