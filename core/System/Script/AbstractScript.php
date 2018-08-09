<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 20:06
 */

namespace EApp\System\Script;

use Composer\IO\IOInterface;

abstract class AbstractScript
{
	/**
	 * @var IOInterface
	 */
	protected $IO;

	public function __construct(IOInterface $IO)
	{
		$this->IO = $IO;
	}

	/**
	 * @return IOInterface
	 */
	public function getIO()
	{
		return $this->IO;
	}

	abstract public function run();

	// api

	protected function getBaseDir(): string
	{
		static $base_dir = null;
		if( !$base_dir )
		{
			$base_dir = getcwd() . DIRECTORY_SEPARATOR;
		}
		return $base_dir;
	}
}