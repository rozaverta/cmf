<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\System;

use EApp\App;
use EApp\Cache;
use EApp\Component\QueryModules;
use EApp\Prop;
use EApp\System\Console\ClassReader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

class Terminal
{
	private $name;
	private $version;
	private $commands = [];
	private $wait = false;

	public function __construct( $name = null, $version = null )
	{
		if( !CONSOLE_MODE )
		{
			throw new \Exception("Run php as cli");
		}

		$system = Prop::cache("system");
		if( $system->get("update") === true )
		{
			$this->wait = true;
		}

		if( !$name )
		{
			$name = $system->getIs("name") ? $system->get("name") : "Elastic CMF";
		}

		if( !$version )
		{
			$version = $system->getIs("version") ? $system->get("version") : App::VER;
		}

		$this->name = $name;
		$this->version = $version;
	}

	public function run()
	{
		if( $this->wait )
		{
			$out = new ConsoleOutput();
			$out->writeln('<info>Warning!</> System is update, please wait');
			return 0;
		}

		$application = new Application( $this->name, $this->version );

		// ... register commands
		foreach( $this->commands as $key => $data )
		{
			// add $key
			if( $data["count"] > 0 )
				foreach($data["items"] as $command)
				{
					$name = $command["class_name"];
					$application->add(new $name( $command ));
				}
		}

		return $application->run();
	}

	public function load( $dir )
	{
		if( $this->wait )
		{
			return false;
		}

		if( DIRECTORY_SEPARATOR !== "/" ) {
			$dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);
		}

		if( !is_dir($dir) )
		{
			return false;
		}

		$dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$key = md5($dir);

		if( isset($this->commands[$key]) )
		{
			return $this->commands[$key]["count"];
		}

		$cache = new Cache($key, "console");

		if( $cache->ready() )
		{
			$this->commands[$key] = $cache->getContentData();
		}
		else
		{
			$scn = @ scandir($dir);

			if( !is_array($scn) )
			{
				throw new \Exception("Can't ready terminal directory");
			}

			$map = [
				"count" => 0,
				"path" => $dir,
				"items" => []
			];

			foreach( $scn as $file )
			{
				if( $file[0] !== "." && preg_match('/^[a-z][a-z0-9]*\.php$/i', $file) )
				{
					$reader = new ClassReader( $dir . $file );
					if( $reader->ready() )
					{
						$row =
							[
								"name" => preg_replace_callback('/[A-Z]/', static function($m) { return '-' . lcfirst($m[0]); }, lcfirst($reader->get("name"))),
								"class_name" => $reader->getClassName()
							];

						if( $reader->getIs("description") )
						{
							$row["description"] = $reader->get("description");
						}
						else if( $reader->getIs(0) )
						{
							$row["description"] = $reader->get(0);
						}

						if( $reader->getIs("help") )
						{
							$row["help"] = $reader->get("help");
						}

						if( $reader->getIs("author") )
						{
							$row["author"] = $reader->get("author");
						}

						$map["count"] ++;
						$map["items"][] = $row;
					}
				}
			}

			$this->commands[$key] = $map;
			//$cache->write($map);
		}

		return $this->commands[$key]["count"];
	}

	public function loadDefault()
	{
		static $load = -1;

		if( $this->wait )
		{
			return 0;
		}

		if( $load > -1 )
		{
			return $load;
		}

		$load = $this->load( CORE_DIR . "Console" );
		if( $load === false )
		{
			throw new \Exception("Default commands not registered");
		}

		if( SYSTEM_INSTALL )
		{
			foreach( (new QueryModules())
				         ->filter("install", true)
				         ->get() as $module )
			{
				/** @var \EApp\Component\Scheme\ModuleSchemeDesigner $module */
				$count = $this->load( $module->path . "Console" );
				if( $count !== false )
				{
					$load += $count;
				}
			}
		}

		return $load;
	}
}