<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 20:06
 */

namespace EApp\System\Script;

use Composer\IO\IOInterface;

abstract class AbstractAddonsScript extends AbstractScript
{
	/**
	 * @var string
	 */
	protected $name;

	public function __construct(IOInterface $IO, string $name)
	{
		parent::__construct($IO);
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
}