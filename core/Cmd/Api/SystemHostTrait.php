<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 12:13
 */

namespace EApp\Cmd\Api;

use EApp\Helper;
use EApp\Host;
use EApp\Prop;

trait SystemHostTrait
{
	use IOTrait;

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return Helper::isSystemHost();
	}

	public function isInstall(): bool
	{
		return Helper::isSystemInstall(true);
	}

	public function inInstallUpdateProgress(): bool
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$system = Prop::file("system");
		$status = $system["status"] ?? "";
		return in_array($status, ["install-progress", "update-progress", "progress"]);
	}

	/**
	 * @return Host
	 */
	protected function getHost(): Host
	{
		if( $this->isHost() )
		{
			return Host::getInstance();
		}

		$io = $this->getIO();

		// provide host
		$host_name = $io->ask('Provide a hostname: ');
		$host_name = trim($host_name);
		if( ! strlen($host_name) )
		{
			return $this->getHost();
		}

		// exit ?
		if( $host_name === 'exit' && $io->confirm("Exit (y/n)? ") )
		{
			exit;
		}

		// reload
		$host = Host::getInstance();
		try {
			$reload = $host->reload($host_name);
		}
		catch( \InvalidArgumentException $e ) {
			$io->write("<error>Warning:</error> " . $e->getMessage());
			return $this->getHost();
		}

		// select host nof found, find else
		if( !$reload )
		{
			$io->write("<error>Warning:</error> The '{$host_name}' host not found");
			return $this->getHost();
		}

		// redirect host ?
		if($host->getStatus() === "redirect")
		{
			$io->write("<error>Warning:</error> The '{$host_name}' host is already used for redirection, select another");
			return $this->getHost();
		}

		// select host, define constants
		$host->define();

		// check install or update process
		if( $this->inInstallUpdateProgress() )
		{
			throw new \InvalidArgumentException("Warning! The process of installing or updating the system was started, please wait");
		}

		return Host::getInstance();
	}
}