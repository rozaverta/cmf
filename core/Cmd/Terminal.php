<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Cmd;

use EApp\App;
use EApp\Cache;
use EApp\Component\ModuleConfig;
use EApp\Component\QueryModules;
use EApp\Event\EventManager;
use EApp\Helper;
use EApp\ModuleCoreConfig;
use EApp\Prop;
use EApp\Cmd\Api\PhpCommentClass;
use EApp\Events\ThrowableEvent;
use EApp\Filesystem\Resource;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Terminal
{
	private $name;
	private $version;

	public function __construct()
	{
		if( ! defined('CORE_DIR') )
		{
			throw new \Exception("System is not loaded");
		}

		if( ! defined('CONSOLE_MODE') || ! CONSOLE_MODE )
		{
			throw new \Exception("Run php as cli");
		}

		if( Helper::isSystemHost() )
		{
			$system = Prop::file("system");
			if( in_array($system["status"] ?? "", ["install-progress", "update-progress"]) )
			{
				throw new \Exception("Warning! System is update, please wait");
			}
		}

		// load manifest resource
		$ref = new \ReflectionClass(App::class);
		$manifest = new Resource("manifest", dirname($ref->getFileName()) . DIRECTORY_SEPARATOR . "resources");
		if( $manifest->getType() !== "#/system" )
		{
			throw new \InvalidArgumentException("Invalid manifest file type");
		}

		$this->name = $manifest->getOr("title", "Elastic CMS");
		$this->version = $manifest->get("version");
	}

	public function run()
	{
		$application = new Application( $this->name, $this->version );

		// register all commands

		$this->load($application);

		// add system throwable
		$dispatcher = new EventDispatcher();
		$dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
			EventManager::dispatch( new ThrowableEvent( $event->getError() ));
		});

		$application->setDispatcher($dispatcher);

		return $application->run();
	}

	public function load(Application $application)
	{
		static $load = -1;

		if( $load > -1 )
		{
			return $load;
		}

		$load = $this->loadModule( $application, new ModuleCoreConfig() );
		if( $load === false )
		{
			throw new \Exception("Default commands not registered");
		}

		if( Helper::isSystemInstall(true) )
		{
			foreach( (new QueryModules())
				         ->filter("install", true)
				         ->get() as $module )
			{
				/** @var \EApp\Component\Scheme\ModulesSchemeDesigner $module */
				$count = $this->loadModule( $application, $module->getConfig() );
				if( $count !== false )
				{
					$load += $count;
				}
			}
		}

		return $load;
	}

	private function loadModule( Application $application, ModuleConfig $config )
	{
		$path = $config->getPath() . "CmdCommands";
		if( ! is_dir($path) )
		{
			return false;
		}

		$name_space = $config->getNamespace() . "CmdCommands\\";
		$key = md5($path);

		if( Helper::isSystemInstall() )
		{
			$cache = new Cache($key, "console");
			if( $cache->ready() )
			{
				$commands = $cache->import();
				foreach($commands as $command)
				{
					$class_name = $command["class_name"];
					$application->add(new $class_name( $command ));
				}
				return count($commands);
			}
		}

		try {
			$iterator = new \FilesystemIterator($path);
		}
		catch( \UnexpectedValueException $e ) {
			throw new \Exception("Cannot ready terminal directory: " . $e->getMessage());
		}

		$commands = [];

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
				// valid only \EApp\Cmd\CmdCommand
				$comment = new PhpCommentClass($class_name);
				if( ! $comment->getReflection()->isSubclassOf(CmdCommand::class) )
				{
					continue;
				}

				// set base properties
				// command name, class name, description, help, author
				$command =
					[
						"name" => preg_replace_callback('/[A-Z]/', static function($m) { return '-' . lcfirst($m[0]); }, lcfirst($name)),
						"class_name" => $class_name
					];

				if( $comment->hasDescription() )
				{
					$command["description"] = (string) $comment->getDescription();
				}

				if( $comment->hasParam("help") )
				{
					$command["help"] = (string) $comment->getParam("help");
				}

				if( $comment->hasParam("author") )
				{
					$command["author"] = (string) $comment->getParam("author");
				}

				$class_name = $command["class_name"];
				$application->add(new $class_name( $command ));

				$commands[] = $command;
			}
		}

		$length = count($commands);
		if( $length < 1 )
		{
			return 0;
		}

		if( isset($cache) )
		{
			$cache->export($commands);
		}

		return $length;
	}
}