<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:15
 */

namespace EApp\Cache;

interface CacheStoreInterface
{
	public function getValue( KeyName $key_name, int $life = null ): CacheValueInterface;

	public function flush( string $prefix = null ): bool;

	public function getKeyName( string $key_name, string $prefix = "", array $properties = [] ): KeyName;
}