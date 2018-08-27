<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 13:00
 */

namespace EApp\CmdCommands;

use EApp\Cmd\CmdCommand;

/**
 * System logs
 *
 * @package EApp\CmdCommands
 */
class Logs extends CmdCommand
{
	protected function exec()
	{
		$script = new Scripts\Logs($this->getIO());
		$script->menu();
	}
}