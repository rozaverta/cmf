<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.08.2018
 * Time: 23:01
 */

namespace EApp\CmdCommands;

use EApp\Cmd\CmdCommand;

/**
 * Cmf update and remove system
 *
 * Class Cmf
 *
 * @package EApp\CmdCommands
 */
class Cmf extends CmdCommand
{
	protected function exec()
	{
		$script = new Scripts\Cmf($this->getIO());
		$script->menu();
	}
}