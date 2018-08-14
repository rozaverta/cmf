<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.07.2018
 * Time: 20:10
 */

namespace EApp\System\ConsoleCommands\Traits;

trait SystemInfoTrait
{
	protected function hasInstall(): bool
	{
		return defined("SYSTEM_INSTALL") && SYSTEM_INSTALL === true;
	}

	protected function useHost(): bool
	{
		return defined("BASE_DIR") && file_exists( BASE_DIR . "host.php" );
	}

	protected function hasInstallProgress( string $host = "*" ): bool
	{
		if( $this->hasInstall() || ! defined("BASE_DIR") )
		{
			return false;
		}

		$file = BASE_DIR . "install-progress.php";
		if( !file_exists($file) )
		{
			return false;
		}

		$info = \E\IncludeContentFile($file);
		$progress = $info["progress"] ?? false;
		if( !$progress )
		{
			return false;
		}

		if( $host === "*" )
		{
			$host = defined("APP_HOST") ? APP_HOST : "";
		}

		return strlen($host) ? ( $host === $info["host"] ) : false;
	}
}