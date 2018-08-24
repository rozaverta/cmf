<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2018
 * Time: 14:10
 */

namespace EApp\CmdCommands\Scripts;

use EApp\Cmd\IO\Option;

class Cache extends AbstractScript
{
	protected function init()
	{
		$this->getHost();
	}

	public function menu()
	{
		$variant = $this
			->getIO()
			->askOptions([
				new Option("clear all cache", 1),
				new Option("clear prefix cache", 2),
				new Option("show info", 3),
				new Option("stats", 4),
				new Option("exit")
			]);

		switch($variant)
		{
			case 1: $this->flush(); break;
			case 2: $this->flushAskPrefix(); break;
			case 3: $this->info(); break;
			case 4: $this->stats(); break;
		}
	}

	public function flush( string $prefix = null )
	{
		try {
			$clean = \EApp\Cache
				::store()
				->flush($prefix);

			if( !$clean )
			{
				throw new \Exception("Cannot flush cache, system error");
			}

			$this
				->getIO()
				->write("<info>Success!</info> The cache data was successfully deleted");
		}
		catch(\Exception $e)
		{
			$this
				->getIO()
				->write("<error>Wrong!</error> " . $e->getMessage());
		}
	}

	public function info()
	{
		$this
			->getIO()
			->write("TODO: you select - INFO");
	}

	public function stats()
	{
		$this
			->getIO()
			->write("TODO: you select - FLUSH");
	}

	private function flushAskPrefix()
	{
		$prefix = trim( $this->getIO()->ask("Enter cache prefix: ") );
		if( !strlen($prefix) )
		{
			$this->flushAskPrefix();
		}
		else
		{
			$this->flush($prefix);
		}
	}

	private function getSizeUnits(int $size): string
	{
		static $units = [ ' bytes', ' Kb', ' Mb', ' Gb', ' Tb', ' Pb', ' Eb', ' Zb', ' Yb' ];
		if( $size < 1 )
		{
			return '0 bytes';
		}
		if( $size < 1024 )
		{
			return $size . ( $size === 1 ? ' byte' : ' bytes');
		}

		$power = (int) floor( log($size, 1024) );
		return number_format($size / pow(1024, $power), 2, '.', ',') . $units[$power];
	}
}