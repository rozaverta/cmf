<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:54
 */

namespace EApp\Cache\Database;

use EApp\Cache\DatabaseHash;
use EApp\Cache\Hash;
use EApp\Cache\Factory;
use EApp\Database\Connection;
use EApp\Database\Query\Builder;

class DatabaseFactory extends Factory
{
	use DatabaseConnectionTrait;

	private $ready = false;

	private $life = 0;

	/**
	 * @var null | object
	 */
	private $row = null;

	public function __construct( Connection $connection, string $table, Hash $key_name )
	{
		if( ! $key_name instanceof DatabaseHash )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseHash::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}

		parent::__construct( $key_name );

		$this->setConnection($connection, $table);
	}

	public function load( int $life = 0 )
	{
		$this->life = $life;
	}

	public function has(): bool
	{
		if( ! $this->ready )
		{
			$this->ready = true;

			$row = $this->fetch(function(Builder $table) {
				return $table->first([
					'id', 'value', 'updated_at'
				]);
			}, $this->tableThen());

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

		return ! is_null($this->row);
	}

	public function set( string $value ): bool
	{
		$data = [
			'value' => $value,
			'size' => strlen($value),
			'updated_at' => date(
				$this->getConnection()->getQueryGrammar()->getDateTimeFormatString()
			)
		];

		if( $this->has() )
		{
			$id = $this->row->id;
			$update = $this->fetch(
				function(Builder $table) use($data) {
					return $table->update($data) !== false;
				},
				$this->tableThen(false, $id)
			);

			if( !$update )
			{
				return false;
			}
		}
		else
		{
			$data["key_name"]   = $this->key_name->keyName();
			$data["key_prefix"] = $this->key_name->keyPrefix();

			$id = $this->fetch(
				function(Builder $table) use($data) {
					return $table->insertGetId($data);
				},
				$this->tableThen(false)
			);

			if( !$id )
			{
				return false;
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
		$delete = $this->fetch(
			function(Builder $table) {
				return $table->delete() !== false;
			},
			$this->tableThen()
		);

		if( !$delete )
		{
			return false;
		}

		$this->ready = false;
		$this->row = null;

		return true;
	}

	protected function tableThen( bool $key_name = true, int $where_id = 0 ): Builder
	{
		$table = $this->table();

		if($key_name)
		{
			$table
				->where('key_name', '=', $this->key_name->keyName())
				->where('key_prefix', '=', $this->key_name->keyPrefix());
		}
		else if( $where_id > 0 )
		{
			$table
				->whereId($where_id);
		}

		return $table;
	}
}