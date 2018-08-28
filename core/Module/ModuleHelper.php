<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 16:48
 */

namespace EApp\Module;

use EApp\Schemes\ModulesSchemeDesigner;
use EApp\Database\Manager as DB;
use EApp\Database\Schema\SchemeDesigner;
use EApp\Exceptions\NotFoundException;
use EApp\Helper;
use EApp\Support\Str;

final class ModuleHelper
{
	private function __construct() {}

	/**
	 * Format and validate module name
	 *
	 * @param string $name
	 * @return bool|string
	 */
	public static function toNameStrict( string $name )
	{
		$len = strlen($name);
		if( $len < 1 || $len > 50 )
		{
			return false;
		}

		$name = str_replace("-", "_", $name);
		if(strpos($name, "__") !== false || $name[0] === "_" || $len > 1 && $name[$len-1] === "_")
		{
			return false;
		}

		if( strpos($name, "_") !== false || ! ctype_upper($name[0]) )
		{
			$name = self::toName($name);
		}

		if( is_numeric($name[0]) || preg_match('/[^a-zA-Z0-9]/', $name ) )
		{
			return false;
		}

		return $name;
	}

	/**
	 * Convert key to module name
	 *
	 * @param string $name
	 * @return string
	 */
	public static function toKey(string $name): string
	{
		return Str::snake($name);
	}

	/**
	 * Convert module name to key
	 *
	 * @param string $key
	 * @return string
	 */
	public static function toName(string $key): string
	{
		return Str::studly($key);
	}

	/**
	 * Has module install
	 *
	 * @param string $name
	 * @param bool $result_id
	 * @return bool
	 */
	public static function hasInstall( string $name, bool $result_id = false ): bool
	{
		if( $name === "@core" )
		{
			return Helper::isSystemInstall(true) ? ($result_id ? 0 : true) : false;
		}

		try {
			$row = self::getModuleScheme($name, false, ["id", "install"]);
		}
		catch(NotFoundException $e) {
			return false;
		}

		if( $row->install < 1 )
		{
			return false;
		}

		return $result_id ? (int) $row->id : true;
	}

	/**
	 * Module exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function exists( string $name ): bool
	{
		if( $name === "@core" )
		{
			return true;
		}

		try {
			$row = self::getModuleScheme($name, false, ["id"]);
		}
		catch(NotFoundException $e) {
			return false;
		}

		return $row->id > 0;
	}

	/**
	 * @param string $name
	 * @return int
	 * @throws NotFoundException
	 */
	public static function getId( string $name ): int
	{
		if( $name === "@core" )
		{
			return 0;
		}

		$row = self::getModuleScheme($name, false, ["id"]);
		return (int) $row->id;
	}

	/**
	 * @param $name
	 * @return Module
	 * @throws NotFoundException
	 */
	public static function get( $name ): Module
	{
		$row = self::getModuleScheme($name, false, ["id", "install"]);

		$id = (int) $row->id;
		if($row->install > 0)
		{
			return new Module($id);
		}
		else
		{
			return new ModuleFake($id);
		}
	}

	/**
	 * @param $name
	 * @return ModulesSchemeDesigner
	 * @throws NotFoundException
	 */
	public static function getScheme( $name ): ModulesSchemeDesigner
	{
		return self::getModuleScheme($name);
	}

	/**
	 * @param $name
	 * @param bool $scheme
	 * @param array $columns
	 * @return ModulesSchemeDesigner | SchemeDesigner
	 * @throws NotFoundException
	 */
	protected static function getModuleScheme( $name, $scheme = true, $columns = ['*'] )
	{
		$builder = DB::table("modules");

		if( is_numeric($name) )
		{
			$builder->whereId((int) $name);
		}
		else
		{
			$builder->where("name", self::toName($name));
		}

		if( $scheme )
		{
			$builder->setResultClass(ModulesSchemeDesigner::class);
		}

		$row = $builder->first($columns);
		if( !$row )
		{
			throw new NotFoundException("The '{$name}' module not found");
		}

		return $row;
	}
}