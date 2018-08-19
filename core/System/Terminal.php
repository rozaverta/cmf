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
use EApp\Event\EventManager;
use EApp\Prop;
use EApp\Proto\ConsoleCommand;
use EApp\System\ConsoleCommands\Api\PhpCommentClass;
use EApp\System\Events\ThrowableEvent;
use EApp\System\Fs\FileResource;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Terminal
{
	private $name;
	private $version;
	private $commands = [];
	private $wait = false;

	public function __construct()
	{
		if( ! defined('CORE_DIR') )
		{
			throw new \Exception("System is not loaded");
		}

		if( ! CONSOLE_MODE )
		{
			throw new \Exception("Run php as cli");
		}

		// load manifest resource
		$ref = new \ReflectionClass(App::class);
		$manifest = new FileResource("manifest", dirname($ref->getFileName()) . DIRECTORY_SEPARATOR . "resources");
		if( $manifest->getType() !== "#/system" )
		{
			throw new \InvalidArgumentException("Invalid manifest file type");
		}

		$system = Prop::cache("system");

		$this->wait = in_array($system->get("status"), ["install-progress", "update-progress"]);
		$this->name = $system->getOr("name", $manifest->get("name"));
		$this->version = $system->getOr("version", $manifest->get("version"));
	}

	public function run()
	{
		if( $this->wait )
		{
			$out = new ConsoleOutput();
			$out->writeln('<info>Warning!</info> System is update, please wait');
			return 0;
		}

		$application = new Application( $this->name, $this->version );

		// ... register all commands
		foreach( $this->commands as $key => $data )
		{
			if( $data["count"] > 0 )
				foreach($data["items"] as $command)
				{
					$class_name = $command["class_name"];
					$application->add(new $class_name( $command ));
				}
		}

		// add system throwable
		$dispatcher = new EventDispatcher();
		$dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
			EventManager::dispatch( new ThrowableEvent( $event->getError() ));
		});

		$application->setDispatcher($dispatcher);

		return $application->run();
	}

	public function load( $dir, $name_space )
	{
		if( $this->wait )
		{
			return false;
		}

		if( DIRECTORY_SEPARATOR !== "/" )
		{
			$dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);
		}

		if( $dir[strlen($dir) - 1] !== DIRECTORY_SEPARATOR )
		{
			$dir .= DIRECTORY_SEPARATOR;
		}

		$dir .= "ConsoleCommands";
		$name_space .= "ConsoleCommands\\";

		if( !is_dir($dir) )
		{
			return false;
		}

		$key = md5($dir);

		if( isset($this->commands[$key]) )
		{
			return $this->commands[$key]["count"];
		}

		$cache = new Cache($key, "console");

		if( $cache->ready() )
		{
			$this->commands[$key] = $cache->import();
		}
		else
		{
			// $name_space
			try {
				$iterator = new \FilesystemIterator($dir);
			}
			catch( \UnexpectedValueException $e ) {
				throw new \Exception("Can't ready terminal directory: " . $e->getMessage());
			}

			$dir .= DIRECTORY_SEPARATOR;
			$map = [
				"count" => 0,
				"path" => $dir,
				"items" => []
			];

			/** @var \SplFileInfo $file */
			foreach( $iterator as $file )
			{
				$name = $file->getFilename();

				// valid file name
				if( $name[0] !== "." && ! $file->isLink() && $file->isFile() && $file->getExtension() === "php" )
				{
					// check class exists
					$name = $file->getBasename(".php");
					$class_name = $name_space . $name;
					if( ! class_exists($class_name, true) )
					{
						continue;
					}

					// check subclass
					// valid only \EApp\Proto\ConsoleCommand
					$comment = new PhpCommentClass($class_name);
					if( !$comment->getReflection()->isSubclassOf(ConsoleCommand::class) )
					{
						continue;
					}

					// set base properties
					// command name, class name, description, help, author
					$row =
						[
							"name" => preg_replace_callback('/[A-Z]/', static function($m) { return '-' . lcfirst($m[0]); }, lcfirst($name)),
							"class_name" => $class_name
						];

					if( $comment->hasDescription() )
					{
						$row["description"] = (string) $comment->getDescription();
					}

					if( $comment->hasParam("help") )
					{
						$row["help"] = (string) $comment->getParam("help");
					}

					if( $comment->hasParam("author") )
					{
						$row["author"] = (string) $comment->getParam("author");
					}

					$map["count"] ++;
					$map["items"][] = $row;
				}
			}

			$this->commands[$key] = $map;
			// todo $cache->export($map);
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

		$load = $this->load( CORE_DIR . "System", "EApp\\System\\" );
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
				$count = $this->load( $module->path, $module->name_space );
				if( $count !== false )
				{
					$load += $count;
				}
			}
		}

		return $load;
	}
}