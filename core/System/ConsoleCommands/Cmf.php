<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.08.2018
 * Time: 23:01
 */

namespace EApp\System\ConsoleCommands;

use EApp\CI\PhpExport;
use EApp\Component\Driver\ModuleComponentCore;
use EApp\Log;
use EApp\Prop;
use EApp\Proto\ConsoleCommand;

/**
 * Cmf update and remove system
 *
 * Class Cmf
 *
 * @package EApp\System\ConsoleCommands
 */
class Cmf extends ConsoleCommand
{
	protected function exec()
	{
		$script = new Scripts\Cmf($this->getIO());
		$script->menu();











		if(true) {
			return;
		}
		$this->getHost();

		// check install or update process
		if( $this->hasInstallUpdateProgress() )
		{
			return $this->write("<error>Warning:</error> Terminate the cron launch, because the process of installing or updating the system was started");
		}

		$v = [];
		if( $this->hasInstall() )
		{
			$n = ["<info>1</info> Update cmf", "<info>2</info> Uninstall cmf", "<info>3</info> Exit"];
			$v["1"] = "update";
			$v["2"] = "remove";
			$v["3"] = "exit";
		}
		else
		{
			$n = ["<info>1</info> Install cmf", "<info>2</info> Exit"];
			$v["1"] = "install";
			$v["2"] = "exit";
		}

		foreach($n as $i)
		{
			$this->write($i);
		}

		$action = $this->askMe($v);

		if( $action !== "exit" )
		{
			if( $action === "install" ) $this->install();
			if( $action === "update" ) $this->update();
			if( $action === "uninstall" ) $this->uninstall();
		}
	}

	private function askMe(array $variant)
	{
		$a = $this->ask("Enter variant: ");
		$a = trim($a);

		if( !isset($variant[$a]) )
		{
			$this->write("Enter " . implode(" or ", array_keys($variant)));
			return $this->askMe($variant);
		}

		return $variant[$a];
	}

	private function install()
	{
		// assets
		$this->checkDir(ASSETS_DIR, "assets", true);

		// application
		$this->checkDir(APP_DIR, "application");

		$app_dirs = [
			"config"    => false,
			"resources" => false,
			"logs"      => true,
			"cache"     => true,
			"addons"    => true,
			"view"      => true
		];

		foreach($app_dirs as $dir => $www_data)
		{
			$this->checkDir( APP_DIR . $dir, $dir, $www_data );
		}

		// configs

		$conf_dir = APP_DIR . "config" . DIRECTORY_SEPARATOR;

		// 1. /system.php
		$file = $conf_dir . "system.php";
		if( ! file_exists($file) )
		{
			$data = $this->installConfigSystem();
			$data["install"] = false;

			$this->writePhpFile($file, $data);
		}

		// 2. /boot.php
		$file = $conf_dir . "boot.php";
		if( ! file_exists($file) )
		{
			$this->writePhpFile($file, null);
		}

		// 3. /uri.php
		$file = $conf_dir . "uri.php";
		if( ! file_exists($file) )
		{
			$this->writePhpFile($file, $this->installConfigUri());
		}

		// 4. /db.php
		$file = $conf_dir . "db.php";
		$data = Prop::file("db");
		if( ! isset($data['default']) || $this->confirm("Override database config (y/n)? "))
		{
			$data["default"] = $this->installConfigDb( $data["default"] ?? [] );
			$this->writePhpFile($file, $data);
		}

		// Check database connection

		try {
			\DB::connection()->reconnect();
			$this->write("<info>$</info> Database connection is created");
		}
		catch( \Exception $e ) {
			throw new \InvalidArgumentException("Error database connection: " . $e->getMessage());
		}

		if( $this->confirm("<info>$</info> The basic setting was successful. Do you want to run the installation (y/n)? ") )
		{
			$drv = new ModuleComponentCore();
			$drv->addCaptureLogListener(function(Log $log) {
				$t = $log->level === "ERROR" ? "error" : "info";
				$e = $log->level === "ERROR" ? "Error:" : "\$";
				$log->translateOff();
				$this->write("<{$t}>" . $e . "</{$t}> " . $log->message());
			});
			$drv->install();
		}
	}

	private function writePhpFile(string $file, $content, bool $www_data = false)
	{
		$text = '<?php defined("ELS_CMS") || exit;' . "\n";

		if( is_array($content) )
		{
			$text .= PhpExport
					::getInstance()
						->config(PhpExport::SHORT_ARRAY_SYNTAX | PhpExport::ARRAY_PRETTY_PRINT)
						->data($content) . "\n";
		}
		else if( is_string($content) )
		{
			$text .= $content;
		}

		$exists = file_exists($file);

		if( $fo = @ fopen( $file, "wa+" ) )
		{
			if( @ flock( $fo, LOCK_EX ) )
			{
				flock(  $fo, LOCK_UN );
				fwrite( $fo, $text );
				fflush( $fo );
				flock(  $fo, LOCK_UN );
			}

			@ fclose( $fo );

			$ready = @ file_get_contents($file);

			if( $ready && md5($ready) === md5($text) )
			{
				$this->write("<info>\$</info> The {$file} file was successfully " . ($exists ? "updated" : "created"));
				$www_data && $this->chownWwwData($file);
				return;
			}

			file_exists($file) && @ unlink($file);
		}

		throw new \InvalidArgumentException("<error>Error:</error> cannot create the config file {$file}");
	}

	private function chownWwwData(string $file)
	{
		if( function_exists('chown') )
		{
			if( @ chown($file, "www-data") ) $this->write("<info>$ chown</info> www-data");
			else $this->write("<error>Wrong:</error> chown error, cannot change user info");
		}

		if( function_exists('chgrp') )
		{
			if( @ chgrp($file, "www-data") ) $this->write("<info>$ chgrp</info> www-data");
			else $this->write("<error>Wrong:</error> chgrp error, cannot change group info");
		}
	}

	private function checkDir(string $dir, string $type, bool $www_data = false)
	{
		$dir = rtrim( $dir, DIRECTORY_SEPARATOR );
		if( ! is_dir($dir) )
		{
			if( is_file($dir) ) throw new \InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is file");
			if( is_link($dir) ) throw new \InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is link");
			if( ! @ mkdir($dir, $www_data ? 0777 : 0644) ) throw new \InvalidArgumentException("Cannot create the {$type} dir '{$dir}'");

			$this->write("<info>\$</info> Create the {$type} directory: {$dir}");
			$www_data && $this->chownWwwData($dir);
		}
	}

	private function installConfigSystem()
	{
		$this->write("");
		$this->write("<info>$ --</info> System config");

		$e = [
			[
				"name" => "site_name",
				"title" => "Enter site name",
				"short_name" => "Site name"
			],

			[
				"name" => "debug",
				"title" => "Debug global [<info>%s</info>]",
				"default" => true,
				"type" => "bool",
				"short_name" => "Debug"
			],

			[
				"name" => "debug_level",
				"title" => "Debug level",
				"short_name" => "Debug level",
				"ignore_empty" => true,
				"variant" => [
					"all",
					"info",
					"debug",
					"error"
				]
			]
		];

		return $this->fillConfig($e);
	}

	private function installConfigUri()
	{
		$this->write("");
		$this->write("<info>$ --</info> Uri config");

		$e = [
			[
				"name" => "mode",
				"title" => "Enter rewrite uri mode [<info>%s</info>]",
				"short_name" => "Rewrite mode",
				"default" => "rewrite",
				"variant" => [
					"rewrite", "get"
				]
			]
		];

		return $this->fillConfig($e);
	}

	private function installConfigDb( $load = [] )
	{
		if( !is_array($load) )
		{
			$load = [];
		}

		$this->write("");
		$this->write("<info>$ --</info> Database info (default connection)");

		$e = [
			[
				"name" => "driver",
				"title" => "Driver [<info>%s</info>]",
				"default" => "mysql",
				"short_name" => "Database driver",
				"variant" => [
					"mysql"
				]
			],

			[
				"name" => "host",
				"title" => "Enter host name [<info>%s</info>]",
				"default" => "localhost",
				"short_name" => "Database host name"
			],

			[
				"name" => "port",
				"title" => "Enter port",
				"short_name" => "Database port",
				"type" => "int"
			],

			[
				"name" => "database",
				"required" => true,
				"title" => "Enter base name",
				"short_name" => "Database name"
			],

			[
				"name" => "prefix",
				"title" => "Enter table prefix",
				"short_name" => "Database table prefix"
			],

			[
				"name" => "charset",
				"title" => "Enter charset [<info>%s</info>]",
				"short_name" => "Database charset",
				"default" => "utf8"
			],

			[
				"name" => "collation",
				"title" => "Enter charset collation [<info>%s</info>]",
				"short_name" => "Database collation",
				"default" => "{charset}_general_ci"
			],

			[
				"name" => "username",
				"title" => "Enter user name [<info>%s</info>]",
				"short_name" => "Database user",
				"default" => "root"
			],

			[
				"name" => "password",
				"title" => "Enter password",
				"short_name" => "Database password"
			]
		];

		return $this->fillConfig($e, $load);
	}

	private function fillConfig( array $e, array $load = [] )
	{
		// enter data

		foreach($e as $item)
		{
			if( is_string($item) )
			{
				$this->write($item);
			}
			else
			{
				$name = $item["name"];
				$val = $this->getAsk($item, $load);

				if( is_string($val) && ! strlen($val) && (
					isset($load[$name]) ||
					isset($item["ignore_empty"]) && $item["ignore_empty"]
					) ) continue;

				$load[$name] = $val;
			}
		}

		// check data

		$this->write("");
		$this->write("<info>$ -----------------</info>");
		$this->write("<info>$ --</info> Check the data");
		$this->write("<info>$ -----------------</info>");

		foreach($e as $item)
		{
			if( is_array($item) )
			{
				$name = $item["name"];
				if( isset($load[$name]) )
				{
					$val = $load[$name];

					if( is_bool($val) ) $val = $val ? '<info>yes</info>' : '<error>no</error>';
					else if( is_int($val) ) $val = '<comment>' . $val . '</comment>';
					else $val = '<info>' . $val . '</info>';

					$this->write($item["short_name"] . ": " . $val);
				}
			}
		}

		// confirm

		if( !$this->confirm("Continue (y/n)? ") )
		{
			$this->write("<info>$</info> Confirm");
			return $this->fillConfig($e, $load);
		}

		return $load;
	}

	private function getAsk( array $item, array & $load = null )
	{
		$name    = $item["name"];
		$type    = $item["type"] ?? "";
		$title   = $item["title"];
		$default = $load[$name] ?? $item["default"] ?? "";

		if( $type === "bool" )
		{
			if( !is_bool($default) )
			{
				$default = $default === 'true' || $default === '';
			}

			return $this->confirm(str_replace('%s', ($default ? 'true' : 'false'), $title) . " (y/n): ", $default);
		}

		$default = preg_replace_callback('/\{(.*?)\}/', function($m) use (& $load) {
			$val = $load[$m[1]] ?? "";
			return is_bool($val) ? ($val ? 'true' : 'false') : $val;
		}, $default);

		$result = $this->ask(str_replace('%s', $default, $title) . ": ", $default);
		$result = trim($result);

		if( !strlen($result) )
		{
			if( isset($item["required"]) && $item["required"])
			{
				$this->write("<error>Wrong:</error> " . $item["short_name"] . " is required");
				return $this->getAsk($item, $load);
			}
			return $result;
		}

		if( $type === "int" )
		{
			if(is_numeric($result))
			{
				return (int) $result;
			}

			$this->write("<error>Wrong:</error> " . $item["short_name"] . " must be number");
			return $this->getAsk($item, $load);
		}

		if( isset($item["variant"]) && is_array($item["variant"]) )
		{
			if(in_array($result, $item["variant"]))
			{
				return $result;
			}

			$this->write("<error>Wrong:</error> " . $item["short_name"] . " is invalid, repeat");
			return $this->getAsk($item, $load);
		}

		return $result;
	}

	private function update()
	{
		//
	}

	private function uninstall()
	{
		//
	}
}