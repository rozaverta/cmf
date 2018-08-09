<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 20:06
 */

namespace EApp\System\Script\Module;

use Composer\IO\IOInterface;
use EApp\System\Script\AbstractAddonsScript;

abstract class AbstractModuleScript extends AbstractAddonsScript
{
	/**
	 * @var string
	 */
	protected $name_space;

	public function __construct(IOInterface $IO, string $name, string $name_space)
	{
		parent::__construct($IO, $name);

		$this->name_space = $name_space;
	}

	/**
	 * @return string
	 */
	public function getNameSpace(): string
	{
		return $this->name_space;
	}
}