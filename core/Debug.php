<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.09.2017
 * Time: 3:23
 */

namespace EApp;

final class Debug
{
	public static function timing()
	{
		return microtime(true) - NOW_MICROTIME;
	}

	public static function dbTiming()
	{
		if( \DB::hasInstance() )
		{
			return \DB::connection()->getTiming();
		}
		else
		{
			return 0;
		}
	}

	public static function dbQueries()
	{
		if( \DB::hasInstance() )
		{
			return \DB::connection()->getQueries();
		}
		else
		{
			return 0;
		}
	}

	public static function toArray()
	{
		return
			[
				'timing' => self::timing(),
				'memory' => memory_get_usage( true ),
				'db-timing' => self::dbTiming(),
				'db-queries' => self::dbQueries()
			];
	}

	public static function getBenchmark()
	{
		return new Benchmark();
	}
}