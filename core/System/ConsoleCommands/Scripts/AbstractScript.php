<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:15
 */

namespace EApp\System\ConsoleCommands\Scripts;

use EApp\Prop;
use EApp\System\ConsoleCommands\IO\InputOutputInterface;
use EApp\Host as AppHost;

abstract class AbstractScript
{
	private $IO;

	public function __construct( InputOutputInterface $IO )
	{
		$this->IO = $IO;
	}

	/**
	 * @return InputOutputInterface
	 */
	public function getIO(): InputOutputInterface
	{
		return $this->IO;
	}
	/**
	 * @return bool
	 */
	public function isHost()
	{
		return defined("APP_HOST");
	}

	/**
	 * @return AppHost
	 */
	public function getHost()
	{
		if( $this->isHost() )
		{
			return AppHost::getInstance();
		}

		$io = $this->getIO();

		// provide host
		$host = $io->ask('Provide a hostname: ');
		$host = trim($host);
		if( ! strlen($host) )
		{
			return $this->getHost();
		}

		// exit ?
		if( $host === 'exit' && $io->confirm("Exit (y/n)? ") )
		{
			exit;
		}

		// reload
		$hosts = AppHost::getInstance();
		try {
			$reload = $hosts->reload($host);
		}
		catch( \InvalidArgumentException $e ) {
			$io->write("<error>Warning:</error> " . $e->getMessage());
			return $this->getHost();
		}

		// select host nof found, find else
		if( !$reload )
		{
			$io->write("<error>Warning:</error> The '{$host}' host not found");
			return $this->getHost();
		}

		// redirect host ?
		if($hosts->getStatus() === "redirect")
		{
			$io->write("<error>Warning:</error> Host {$host} is already used for redirection, select another");
			return $this->getHost();
		}

		// select host, define constants
		$hosts->define();

		// check install or update process
		if( $this->hasInstallUpdateProgress() )
		{
			throw new \InvalidArgumentException("Warning! The process of installing or updating the system was started, please wait");
		}

		return AppHost::getInstance();
	}

	protected function hasInstall(): bool
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$system = new Prop("system");
		return $system->getOr("install", false);
	}

	protected function hasInstallUpdateProgress()
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$system = new Prop("system");
		return
			$system->equiv("status", "update-progress") ||
			$system->equiv("status", "install-progress");
	}

	protected function tap( $argument, \Closure $closure )
	{
		return $closure( $argument );
	}
}