<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:25
 */

namespace EApp\CmdCommands\Scripts;


use EApp\Cmd\IO\Option;

class Host extends AbstractScript
{
	public function ls()
	{
		//
	}

	public function create()
	{
		//
	}

	public function remove()
	{
		//
	}

	public function setSsl( bool $value = true )
	{
		//
	}

	public function setAlias( bool $redirect = false )
	{
		//
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
					new Option("enter hostname and open menu", 1),
					new Option("list host", 2),
					new Option("exit")
				]);

			if($variant === 1) $this->hostMenu();
			else if($variant === 2) $this->ls();
		}
	}

	public function hostMenu()
	{
		$host = $this->getHost();

		$variant = $this
			->getIO()
			->askOptions([
				new Option("show full host info", "info"),
				new Option("add alias", "add_alias"),
				new Option("remove alias", "remove_alias"),
				new Option(($host->isSsl() ? "remove" : "add") . " ssl only flag", "ssl"),
				new Option("rename", "rename"),
				new Option("move <comment>\"application\"</comment> to another directory", "move_application"),
				new Option("move <comment>\"assets\"</comment> to another directory", "move_assets"),
				new Option("remove from host list", "remove"),
				new Option("exit")
			], APP_HOST);

		switch( $variant )
		{
			case "info": $this->hostShowInfo($this->loadHost($host->getFile())); break;
		}

		$this->getIO()->write("V: " . $variant);
	}

	private function loadHost(string $file)
	{
		return "";
	}

	private function hostShowInfo($host)
	{
		$io = $this->getIO();
		$hosts = $this->getHost();
		$io->write("<info>{$host}</info>");

		$application = $hosts->getApplicationDir();
		$assets = $hosts->getAssetsDir();
		$config_file = APP_DIR . 'config' . DIRECTORY_SEPARATOR . 'system.php';

		$original = $hosts->getOriginalHostName();
		if( $original !== $host ) $io->write("Original name: {$original}");
		if( $hosts->isSsl() ) $io->write("SSL: <info>yes</info>");
		if( $hosts->getPort() !== 80 ) $io->write("Port: <info>" . $hosts->getPort() . "</info>");
		$io->write("Application directory: {$application}");
		$io->write("Assets directory: {$assets}");
		$io->write("Assets path: " . $hosts->getAssetsPath());
		$io->write("Encoding: " . $hosts->getEncoding());
		$io->write("Debug mode: " . $hosts->getDebugMode());

		if( !is_dir($application) ) $io->write("<error>Warning: </error> Application directory does not exist");
		if( !is_dir($assets) ) $io->write("<error>Warning: </error> Assets directory does not exist");
		if( !file_exists($config_file) ) $io->write("<error>Warning: </error> Config file does not exist");
		else
		{
			// todo
		}
	}
}