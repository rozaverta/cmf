<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:54
 */

namespace EApp\Cache\Database;

use EApp\App;
use EApp\Cache\KeyName;
use EApp\Cache\Value;
use EApp\Database\Connection;
use EApp\Database\QueryException;

class DatabaseValue extends Value
{
	private $connection;

	private $table;

	private $ready = false;

	private $life = 0;

	/**
	 * @var null | object
	 */
	private $row = null;

	public function __construct( Connection $connection, string $table, KeyName $key_name, int $life = 0 )
	{
		if( ! $key_name instanceof DatabaseKeyName )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseKeyName::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}
		parent::__construct( $key_name );
		$this->connection = $connection;
		$this->table = $table;
		$this->life = $life;
	}

	public function load( int $life = 0 )
	{
		$this->life = $life;
		return $this;
	}

	public function has(): bool
	{
		if( ! $this->ready )
		{
			$this->ready = true;
			try {
				$row = $this->table()->first([
					'id', 'value', 'updated_at'
				]);

				if( !$row )
				{
					return false;
				}

				if( $this->life > 0 && (new \DateTime($row->updated_at))->getTimestamp() + $this->life < time() )
				{
					$this->forget();
				}
				else
				{
					$this->row = $row;
				}
			}
			catch(QueryException $e) {
				$this->error($e);
			}
		}

		return ! is_null($this->row);
	}

	public function set( string $value ): bool
	{
		$data = [
			'value' => $value,
			'size' => strlen($value),
			'updated_at' => date(
				$this->connection->getQueryGrammar()->getDateTimeFormatString()
			)
		];

		if( $this->has() )
		{
			$id = $this->row->id;
			try {
				$this
					->table(false, $id)
					->update($data);
			}
			catch( QueryException $e ) {
				return $this->error($e);
			}
		}
		else
		{
			$data["key_name"]   = $this->key_name->getKey();
			$data["key_prefix"] = $this->key_name->getKeyPrefix();

			try {
				$id = $this
					->table(false)
					->insertGetId($data);
			}
			catch( QueryException $e ) {
				return $this->error($e);
			}
		}

		$row = new \stdClass();
		$row->id = $id;
		$row->value = $value;
		$row->updated_at = $data['updated_at'];

		$this->row = $row;
		$this->ready = true;

		return true;
	}

	protected function exportData( $data ): bool
	{
		return $this->set(serialize($data));
	}

	public function get()
	{
		return $this->has() ? $this->row->value : null;
	}

	public function import()
	{
		return $this->has() ? unserialize($this->row->value) : null;
	}

	public function forget(): bool
	{
		try {
			$this->table()->delete();
		}
		catch( QueryException $e ) {
			return $this->error($e);
		}

		$this->ready = false;
		$this->row = null;

		return true;
	}

	protected function table( bool $key_name = true, int $where_id = 0 )
	{
		$table = $this
			->connection
			->table($this->table);

		if($key_name)
		{
			$table
				->where('key_name', '=', $this->key_name->getKeyName())
				->where('key_prefix', '=', $this->key_name->getKeyPrefix());
		}
		else if( $where_id > 0 )
		{
			$table
				->whereId($where_id);
		}

		return $table;
	}

	protected function error(QueryException $error): bool
	{
		App::Log($error);
		return false;
	}
}