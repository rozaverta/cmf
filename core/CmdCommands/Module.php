<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 15:21
 */

namespace EApp\CmdCommands;


use EApp\Cmd\CmdCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Work with modules
 *
 * @package EApp\CmdCommands
 */
class Module extends CmdCommand
{
	protected function init()
	{
		$this->addOption("create", "c", InputOption::VALUE_NONE, "Create new module (for developers)");
		$this->addOption("register", "r", InputOption::VALUE_NONE, "Register module");
		$this->addOption("ls", "l", InputOption::VALUE_NONE, "Show all modules");
	}

	protected function exec()
	{
		$script = new Scripts\Module($this->getIO());

		if( $this->input->getOption("create") )
		{
			$script->create();
		}
		else if( $this->input->getOption("register") )
		{
			$script->register();
		}
		else if( $this->input->getOption("ls") )
		{
			$script->list();
		}
		else
		{
			$script->menu();
		}
	}
}