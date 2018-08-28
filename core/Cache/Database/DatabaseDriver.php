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
use EApp\Cache\Driver;
use EApp\Database\Connection;
use EApp\Database\Query\Builder;

class DatabaseDriver extends Driver
{
	use DatabaseConnectionTrait;

	private $ready = false;

	/**
	 * @var null | object
	 */
	private $row = null;

	public function __construct( Connection $connection, string $table, Hash $hash )
	{
		if( ! $hash instanceof DatabaseHash )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseHash::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}

		parent::__construct( $hash );

		$this->setConnection($connection, $table);
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
			$data["name"]   = $this->hash->keyName();
			$data["prefix"] = $this->hash->keyPrefix();

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

	protected function tableThen( bool $hash = true, int $where_id = 0 ): Builder
	{
		$table = $this->table();

		if($hash)
		{
			$table
				->where('name', '=', $this->hash->keyName())
				->where('prefix', '=', $this->hash->keyPrefix());
		}
		else if( $where_id > 0 )
		{
			$table
				->whereId($where_id);
		}

		return $table;
	}
}