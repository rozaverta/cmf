<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\CmdCommands;

use EApp\Cmd\CmdCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Get system cache info and clean cache data
 *
 * @package EApp\CmdCommands
 */
class Cache extends CmdCommand
{
	protected function init()
	{
		$this->addOption("clear-all", "c", InputOption::VALUE_NONE, "Clear all cache data");
		$this->addOption("info", "i", InputOption::VALUE_NONE, "Show cache driver info");
		$this->addOption("stats", "s", InputOption::VALUE_NONE, "Show cache stats");
	}

	protected function exec()
	{
		$script = new Scripts\Cache($this->getIO());

		if( $this->input->getOption("clear-all") )
		{
			$script->flush();
		}
		else if( $this->input->getOption("info") )
		{
			$script->info();
		}
		else if( $this->input->getOption("stats") )
		{
			$script->stats();
		}
		else
		{
			$script->menu();
		}
	}
}