<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.07.2018
 * Time: 20:07
 */

namespace EApp\System\ConsoleCommands;


use EApp\App;
use EApp\Component\Module;
use EApp\Component\ModuleManager;
use EApp\Log;
use EApp\Proto\ConsoleCommand;
use EApp\Proto\Controller;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

// index.php cron module_name/controller_name
// -> ModuleName\Controller\ControllerNameCron.php

/**
 * For start the tasks of the CRON
 *
 * @package EApp\System\ConsoleCommands
 */
class Cron extends ConsoleCommand
{
	protected $options = [];

	protected $system_options = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction'];

	protected function init()
	{
		$this->addArgument("controller", InputArgument::REQUIRED, "Controller name as module_name/controller_name");

		$argv = $_SERVER['argv'] ?? [];
		$prev = false;
		$options = [];

		foreach( $argv as $key )
		{
			if( strlen($key) > 2 && substr($key, 0, 2) === "--" )
			{
				$prev = false;
				$key = substr($key, 2);

				// set focus option
				if( ! in_array($key, $this->system_options, true) )
				{
					$this->options[] = $key;
					$prev = $key;
					$options[$prev] = 0;
				}
			}
			else if( $key[0] === "-" )
			{
				$prev = false;
			}
			else if( $prev )
			{
				$options[$prev] ++;
			}
		}

		foreach( $options as $key => $number )
		{
			$mode = null;
			if( $number === 0 ) $mode = InputOption::VALUE_NONE;
			else if( $number > 1 ) $mode = InputOption::VALUE_IS_ARRAY;

			$this->addOption($key, null, $mode, "Auto adding --{$key}");
		}
	}

	protected function exec()
	{
		// check host
		if( ! $this->isHost() )
		{
			throw new \InvalidArgumentException("You must specify the host (--host 'host.name')");
		}

		// check install
		if( ! $this->hasInstall() )
		{
			throw new \InvalidArgumentException("System is not installed");
		}

		// check install or update process
		if( $this->hasInstallUpdateProgress() )
		{
			return $this->trace("Terminate the cron launch, because the process of installing or updating the system was started");
		}

		// check controller exists
		$controller = $this->input->getArgument("controller");
		$controller = trim($controller, '/');
		if( ! strlen($controller) || ! strpos($controller, "/") )
		{
			throw new InvalidArgumentException("Invalid controller argument '{$controller}'");
		}

		$c2 = explode("/", $controller, 2);

		$m_id = ModuleManager::getInstance()->getId($c2[0]);
		if( !$m_id )
		{
			throw new InvalidArgumentException("Module '{$c2[0]}' not found");
		}

		$module = Module::cache($m_id);
		$class_name = $module->get("name_space") . "Controller\\" . preg_replace_callback('/(^|\/|_)([a-z])/', static function($m) {
			return ($m[1] === "/" ? "\\" : "") . ucfirst($m[2]);
		}, $c2[1]) . 'Cron';

		if( !class_exists($class_name, true) )
		{
			throw new InvalidArgumentException("Controller '{$controller}' not found");
		}

		$options = [];
		foreach($this->options as $option)
		{
			$options[$option] = $this->input->getOption($option);
		}

		/** @var Controller $ctr */
		$ctr = new $class_name( $module, $options );
		if( ! $ctr instanceof Controller )
		{
			throw new InvalidArgumentException("Controller '{$controller}' must be inherited of \\EApp\\Proto\\Controller");
		}

		$ctr->addCaptureLogListener(function(Log $log) { $this->traceLog($log); });

		if( ! $ctr->ready() )
		{
			throw new InvalidArgumentException("Controller '{$controller}' is not ready");
		}

		$arg_log = $controller;
		foreach( $options as $name => $text )
		{
			$arg_log .= " --" . $name;
			if($text)
			{
				$arg_log .= " ";
				if( is_array($text) )
				{
					$arg_log .= implode(" ", $text);
				}
				else if($text !== true)
				{
					$arg_log .= $text;
				}
			}
		}

		$this->trace("Start cron: {$arg_log}");

		try {
			$ctr->complete();
		}
		catch( \Exception $e ) {
			$this->trace("Fatal error: " . $e->getMessage(), "ERROR");
		}

		$data = $ctr->pageData();
		$head = false;
		$table = new Table( $this->output );
		$table->setHeaders([
			"Name", "Value"
		]);

		foreach( $data as $name => $value )
		{
			$text = $value;

			if( is_bool($value) )
			{
				$text  = $value ? '<info>true</info>' : '<error>false</error>';
				$value = $value ? 'true' : 'false';
			}
			else if( is_int($value) || is_float($value) )
			{
				$text = '<fg=red>' . $value . '</>';
			}
			else if( ! is_string($value) )
			{
				continue;
			}

			if( ! $head )
			{
				$head = true;
				$this->output->writeln("");
				$this->output->writeln("<info>Cron report</info>");
				$this->appLog("Cron report >>");
			}

			$this->appLog( $name . ": " . $value);
			$table->addRow([
				$name, $text
			]);
		}

		if($head)
		{
			$table->render();
			$this->output->writeln("");
		}

		$this->trace("/cron complete: {$controller}");
	}

	protected function appLog($text, $level = "DEBUG")
	{
		// write to journal
		App::Log( new Log($text, $level) );
	}

	protected function traceLog(Log $log)
	{
		// write to journal
		App::Log($log);

		if( $log->level === "DEBUG" ) {
			$text = '<info>Info:</info> ';
		}
		else {
			$text = '<error>' . ucfirst( strtolower( $log->level ) ) . ':</error> ';
		}

		$text .= $log->message();
		$this->output->writeln($text);
	}

	protected function trace($text, $level = "DEBUG")
	{
		$this->traceLog( new Log($text, $level) );
	}
}