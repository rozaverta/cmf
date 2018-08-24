<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 16:34
 */

namespace EApp\Cmd;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use EApp\Cmd\IO\ComposerInputOutput;
use EApp\CmdCommands\Scripts\Init;

class ScriptHandler
{
	public static function initEvent(Event $event)
	{
		if( $event->getName() === "cmf-init" )
		{
			(new Init(new ComposerInputOutput($event->getIO())))->run();
		}
	}

	public static function packageEvent(PackageEvent $event)
	{
		static $names = [
			"post-package-install"  => "install",
			"post-package-update"   => "update",
			"pre-package-uninstall" => "uninstall"
		];

		$io = new ComposerInputOutput($event->getIO());

		// valid event

		$event_name = $event->getName();
		if( $event_name === "pre-package-update" )
		{
			$io->write("TODO: pre package update");
		}

		else if( array_key_exists($event_name, $names) )
		{
			$event_name = $names[$event_name];

			$io->write("TODO: package {$event_name}");

			/** @var InstallOperation | UpdateOperation | UninstallOperation $operation */
			// todo $operation = $event->getOperation();
			// todo $package = $operation->getPackage();
		}
		else
		{
			$io->write("<error>Warning:</error> Unknown action '{$event_name}'");
		}
	}
}