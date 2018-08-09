<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 16:34
 */

namespace EApp\System;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class ScriptHandler
{
	public static function initEvent(Event $event)
	{
		if( $event->getName() === "cmf-init" )
		{
			(new Script\Init($event->getIO()))->run();
		}
	}

	public static function packageEvent(PackageEvent $event)
	{
		static $names = [
			"post-package-install"  => "install",
			"post-package-update"   => "update",
			"pre-package-uninstall" => "uninstall"
		];

		// valid event

		$event_name = $event->getName();
		if( $event_name === "pre-package-update" )
		{
			(new Script\BeforeUpdate($event->getIO()))->run();
			return;
		}

		if( ! array_key_exists($event_name, $names) )
		{
			return;
		}

		$event_name = $names[$event_name];

		/** @var InstallOperation | UpdateOperation | UninstallOperation $operation */
		$operation = $event->getOperation();
		$package   = $operation->getPackage();
		$addons    = [];

		if( $package->getName() === "rozaverta/cmf" )
		{
			$addons[] = [
				"action" => $event_name,
				"type" => "core"
			];
		}
		else
		{
			$extra = $package->getExtra();

			if( isset($extra["rozaverta/cmf"]) && is_array($extra["rozaverta/cmf"]) && count($extra["rozaverta/cmf"]) )
			{
				$extra_addons = $extra["rozaverta/cmf"];
				if( self::hasAddon($extra_addons) )
				{
					$extra_addons = [$extra_addons];
				}

				foreach($extra_addons as $addon)
				{
					if( is_array($addon) && self::hasAddon($addon) )
					{
						$addon["action"] = $addon["type"] . "/" . $event_name;
						$addons[] = $addon;
					}
				}
			}
		}

		$io = $event->getIO();

		if( count($addons) )
			foreach($addons as $addon)
				try {
					switch($addon["action"])
					{
						case "install":             (new Script\Install($io))->run(); break;
						case "update":              (new Script\Update($io))->run(); break;
						case "uninstall":           (new Script\Uninstall($io))->run(); break;

						case "module/install":      (new Script\Module\InstallModule($io, $addon["name"], $addon["name_space"]))->run(); break;
						case "module/update":       (new Script\Module\UpdateModule($io, $addon["name"], $addon["name_space"]))->run(); break;
						case "module/uninstall":    (new Script\Module\UninstallModule($io, $addon["name"], $addon["name_space"]))->run(); break;

						case "language/install":    (new Script\Language\InstallLanguage($io, $addon["name"], $addon["language"]))->run(); break;
						case "language/update":     (new Script\Language\UpdateLanguage($io, $addon["name"], $addon["language"]))->run(); break;
						case "language/uninstall":  (new Script\Language\UninstallLanguage($io, $addon["name"], $addon["language"]))->run(); break;

						case "package/install":     (new Script\Package\InstallPackage($io, $addon["name"]))->run(); break;
						case "package/update":      (new Script\Package\UpdatePackage($io, $addon["name"]))->run(); break;
						case "package/uninstall":   (new Script\Package\UninstallPackage($io, $addon["name"]))->run(); break;
					}
				}
				catch( \InvalidArgumentException $e ) {
					// todo
				}
	}

	private static function hasAddon(array $addon)
	{
		if( array_key_exists("type", $addon) )
		{
			switch($addon["type"])
			{
				case "module":   return isset($addon["name"], $addon["name_space"]); break;
				case "language": return isset($addon["name"], $addon["language"]); break;
				case "package":  return isset($addon["name"]); break;
			}
		}

		return false;
	}
}
