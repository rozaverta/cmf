<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 15:13
 */

namespace EApp\CmdCommands\Scripts;

use EApp\Cmd\IO\Option;

class Module extends AbstractScript
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
				new Option("show modules", "list"),
				new Option("install module", "install"),
				new Option("uninstall module", "uninstall"),
				new Option("register module", "register"),
				new Option("create empty module (for developers)", "create"),
				new Option("exit"),
			]);

		if( $variant && method_exists($this, $variant) )
		{
			$this->{$variant}();
		}
	}

	public function list()
	{
		$this->getIO()->write("TODO " . __METHOD__);
	}

	public function install()
	{
		$this->getIO()->write("TODO " . __METHOD__);
	}

	public function uninstall()
	{
		$this->getIO()->write("TODO " . __METHOD__);
	}

	public function register()
	{
		$this->getIO()->write("TODO " . __METHOD__);
	}

	public function create()
	{
		$this->getIO()->write("TODO " . __METHOD__);
	}
}