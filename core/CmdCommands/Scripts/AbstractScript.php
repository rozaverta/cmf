<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:15
 */

namespace EApp\CmdCommands\Scripts;

use EApp\Cmd\Api\SystemHostTrait;
use EApp\Cmd\IO\InputOutputInterface;

abstract class AbstractScript
{
	use SystemHostTrait;

	public function __construct( InputOutputInterface $IO )
	{
		$this->setIO($IO);
		$this->init();
	}

	protected function init() {}
}