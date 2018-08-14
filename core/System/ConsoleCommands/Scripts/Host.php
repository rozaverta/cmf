<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:25
 */

namespace EApp\System\ConsoleCommands\Scripts;


use EApp\System\ConsoleCommands\IO\Option;

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
		//
	}
}