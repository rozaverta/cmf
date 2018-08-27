<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 17:44
 */

namespace EApp\Component;

use EApp\Schemes\ContextSchemeDesigner;
use EApp\Database\QueryPrototype;

class QueryContext extends QueryPrototype
{
	protected $check_type = false;

	/**
	 * @param string $value
	 * @return $this
	 */
	public function filterType( string $value )
	{
		return $this->filter( "type", "=", $value );
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function filterNotType( string $value )
	{
		return $this->filter( "type", "!=", $value );
	}

	/**
	 * @param string[] $types
	 * @return $this
	 */
	public function filterTypeIn( array $types )
	{
		return $this->filter( "type", "in", $types );
	}

	/**
	 * @param string[] $types
	 * @return $this
	 */
	public function filterTypeNotIn( array $types )
	{
		return $this->filter( "type", "not in", $types );
	}

	/**
	 * @return $this
	 */
	public function filterIsHost()
	{
		return $this->filter( "host", "!=", "" );
	}

	/**
	 * @return $this
	 */
	public function filterIsNotHost()
	{
		return $this->filter( "host", "=", "" );
	}

	/**
	 * @return $this
	 */
	public function filterIsPath()
	{
		return $this->filter( "path", "!=", "" );
	}

	/**
	 * @return $this
	 */
	public function filterIsNotPath()
	{
		return $this->filter( "path", "=", "" );
	}

	/**
	 * @return $this
	 */
	public function filterIsQuery()
	{
		return $this->filter( "query", "!=", "" );
	}

	/**
	 * @return $this
	 */
	public function filterIsNotQuery()
	{
		return $this->filter( "query", "=", "" );
	}

	public function getTableName()
	{
		return "context";
	}

	protected function fetchObject()
	{
		return ContextSchemeDesigner::class;
	}
}