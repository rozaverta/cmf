<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 19:45
 */

namespace EApp\CmdCommands;

use EApp\Cmd\CmdCommand;

class Host extends CmdCommand
{
	protected function exec()
	{
		$script = new Scripts\Host($this->getIO());
		$script->menu();
	}
}