<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:25
 */

namespace EApp\CmdCommands\Scripts;

use EApp\CI\PhpExport;
use EApp\Component\Driver\ModuleComponentCore;
use EApp\Filesystem\Resource;
use EApp\Log;
use EApp\Prop;
use EApp\Cmd\IO\ConfigOption;
use EApp\Cmd\IO\Option;

class Cmf extends AbstractScript
{
	use GetScriptUserTrait;

	public function install()
	{
		$this->getHost();
		if( $this->isInstall() )
		{
			throw new \InvalidArgumentException("System already installed");
		}

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

		// 3. /url.php
		$file = $conf_dir . "url.php";
		if( ! file_exists($file) )
		{
			$this->writePhpFile($file, $this->installConfigUrl());
		}

		$io = $this->getIO();

		// 4. /db.php
		$file = $conf_dir . "db.php";
		$data = Prop::file("db");
		if( ! isset($data['default']) || $io->confirm("Override database config (y/n)? "))
		{
			$data["default"] = $this->installConfigDb( $data["default"] ?? [] );
			$this->writePhpFile($file, $data);
		}

		// Check database connection

		try {
			\DB::connection()->reconnect();
			$io->write("<info>$</info> Database connection is created");
		}
		catch( \Exception $e ) {
			throw new \InvalidArgumentException("Error database connection: " . $e->getMessage());
		}

		if( $io->confirm("<info>$</info> The basic setting was successful. Do you want to run the installation (y/n)? ") )
		{
			$this
				->getDriver()
				->install();
		}
	}

	public function update( bool $force = false )
	{
		$this->getHost();
		$this
			->getDriver()
			->update($force);
	}

	public function uninstall()
	{
		$io = $this->getIO();
		$uninstall =
			$io->confirm("Do you want to delete the system (y/n)? ") &&
			$io->confirm("Are you sure (y/n)? ") &&
			$io->confirm("Really (y/n)? ") &&
			$io->ask("<error>Attention!</error> All data will be deleted. To uninstall the system, enter <comment>UNINSTALL</comment>: ") === "UNINSTALL";

		$io->write("");
		if( !$uninstall )
		{
			$io->write("Ohh, it's possible to breathe out... Otherwise I've already been afraid!");
		}
		else
		{
			$flag = 0;
			if( $io->confirm("Remove database tables (y/n)? ") ) $flag = ModuleComponentCore::UNINSTALL_DATABASE;
			if( $io->confirm("Remove assets files (y/n)? ") ) $flag = $flag | ModuleComponentCore::UNINSTALL_ASSETS;
			if( $io->confirm("Remove application files (y/n)? ") ) $flag = $flag | ModuleComponentCore::UNINSTALL_APPLICATION;

			$this
				->getDriver()
				->uninstall($flag);
		}
	}

	public function menu()
	{
		if( $this->isHost() )
		{
			$this->hostMenu();
		}
		else
		{
			$variant = $this
				->getIO()
				->askOptions([
					new Option("about", 1),
					new Option("enter hostname and open menu", 2),
					new Option("exit")
				]);

			if($variant === 1) $this->about();
			else if($variant === 2) $this->hostMenu();
		}
	}

	public function hostMenu()
	{
		$this->getHost();
		$io = $this->getIO();

		if( $this->isInstall() )
		{
			$variant = $io
				->askOptions([
					new Option("update the system", "update"),
					new Option("update the system forcibly, ignore version", "update_force"),
					new Option("uninstall the system", "uninstall"),
					new Option("exit")
				], APP_HOST);
		}
		else
		{
			$variant = $io
				->askOptions([
					new Option("install the system", "install"),
					new Option("exit")
				], APP_HOST);
		}

		switch( $variant )
		{
			case "install": $this->install(); break;
			case "update":
			case "update_force": $this->update($variant === "update_force"); break;
			case "uninstall": $this->uninstall(); break;
		}

		$this->getIO()->write("V: " . $variant);
	}

	public function about()
	{
		$file = new Resource('manifest', CORE_DIR . 'resources' );
		if( $file->getType() != "#/system" )
		{
			throw new \InvalidArgumentException("Manifest error");
		}

		$io = $this->getIO();

		// info, name, description, version, build, date

		$io->write($file->getOr("name", "Elastic-CMF"));
		$info = ["version", "build", "description", "date"];
		foreach($info as $key)
		{
			if( $file->getIs($key) )
			{
				$value = $file->get($key);
				if( $key === 'version' ) {
					$value = '<info>' . $file->get($key) . '</info>';
				}
				$io->write(ucfirst($key) . ": " . $value);
			}
		}

		// info about author

		$author = $file->getOr("authors", []);
		$at = [];

		if( is_array($author) && count($author) > 0 )
		{
			foreach($author as $one)
			{
				$at[] = $this->getAuthor($one);
			}
		}
		else if( $file->getIs('author') )
		{
			$at[] = $this->getAuthor( $file->get('author') );
		}

		$atn = count($at);
		$atn > 0 && $io->write(($atn > 1 ? 'Authors: ' : 'Author: ') . implode(', ', $at));
	}

	// private

	private function getAuthor($author): string
	{
		if( is_object($author) )
		{
			$author = get_object_vars($author);
		}

		if( ! is_array($author) )
		{
			return (string) $author;
		}

		if( !isset($author['name']) )
		{
			return $author['email'] ?? 'unknown';
		}

		$at = trim($author['name']);
		if( isset($author['email']) )
		{
			$at .= ' <' . $author['email'] . '>';
		}

		return $at;
	}

	private function getDriver(): ModuleComponentCore
	{
		$drv = new ModuleComponentCore();
		$drv->addCaptureLogListener(function(Log $log) {
			$t = $log->level === "ERROR" ? "error" : "info";
			$e = $log->level === "ERROR" ? "Error:" : "\$";
			$log->setTranslate(false);
			$this->getIO()->write("<{$t}>" . $e . "</{$t}> " . $log->message());
		});
		return $drv;
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
				$this
					->getIO()
					->write("<info>\$</info> The {$file} file was successfully " . ($exists ? "updated" : "created"));

				$www_data && $this->chownUserData($file);
				return;
			}

			file_exists($file) && @ unlink($file);
		}

		throw new \InvalidArgumentException("<error>Error:</error> cannot create the config file {$file}");
	}

	private function chownUserData(string $file)
	{
		$user  = $this->getScriptUser();
		$group = $user;
		$io = $this->getIO();

		if( function_exists('chown') )
		{
			if( @ chown($file, $user) ) $io->write("<info>$ chown</info> " . $user);
			else $io->write("<error>Wrong:</error> chown error, cannot change user info");
		}

		if( function_exists('chgrp') )
		{
			if( @ chgrp($file, $group) ) $io->write("<info>$ chgrp</info> " . $group);
			else $io->write("<error>Wrong:</error> chgrp error, cannot change group info");
		}
	}

	private function checkDir(string $dir, string $type, bool $www_data = false)
	{
		$dir = rtrim( $dir, DIRECTORY_SEPARATOR );
		if( ! is_dir($dir) )
		{
			if( is_file($dir) ) throw new \InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is file");
			if( is_link($dir) ) throw new \InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is link");
			if( ! @ mkdir($dir, $www_data ? 0777 : 0755) ) throw new \InvalidArgumentException("Cannot create the {$type} dir '{$dir}'");

			$this
				->getIO()
				->write("<info>\$</info> Create the {$type} directory: {$dir}");

			$www_data && $this->chownUserData($dir);
		}
	}

	private function installConfigSystem()
	{
		return $this
			->getIO()
			->askConfig([
				new ConfigOption("site_name", "Enter site name", ["title" => "Site name"]),
				new ConfigOption("debug", "Debug global [<info>%s</info>]", ["default" => true, "type" => "boolean", "title" => "Debug"]),
				new ConfigOption("debug_level", "Debug level", ["ignore_empty" => true, "enum" => [
					"all",
					"info",
					"debug",
					"error"
				]])
			], "System config");
	}

	private function installConfigUrl()
	{
		return $this
			->getIO()
			->askConfig([
				new ConfigOption("mode", "Enter rewrite url mode [<info>%s</info>]", [
					"title" => "Rewrite mode",
					"default" => "rewrite",
					"enum" => [
						"rewrite",
						"get"
					]])
			], "Url config");
	}

	private function installConfigDb( $load = [] )
	{
		return $this
			->getIO()
			->askConfig([
				new ConfigOption("driver", "Driver [<info>%s</info>]", [
					"title" => "Database driver",
					"default" => "mysql",
					"enum" => [
						"mysql"
					]]),
				new ConfigOption("host", "Enter host name [<info>%s</info>]", ["default" => "localhost", "title" => "Database host name"]),
				new ConfigOption("port", "Enter port", ["title" => "Database port", "type" => "number"]),
				new ConfigOption("database", "Enter base name", ["default" => true, "title" => "Database name"]),
				new ConfigOption("prefix", "Enter table prefix", ["title" => "Database table prefix"]),
				new ConfigOption("charset", "Enter charset [<info>%s</info>]", ["title" => "Database charset", "default" => "utf8"]),
				new ConfigOption("collation", "Enter charset collation [<info>%s</info>]", ["title" => "Database collation", "default" => "{charset}_general_ci"]),
				new ConfigOption("username", "Enter user name [<info>%s</info>]", ["title" => "Database user", "default" => "root"]),
				new ConfigOption("password", "Enter password", ["title" => "Database password"]),
			], "Database info (default connection)", $load);
	}
}