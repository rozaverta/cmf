<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 12:27
 */

namespace EApp\Component\Driver\Traits;

use Doctrine\DBAL\Platforms\MySqlPlatform;

trait DBALToolsTraits
{
	/**
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	protected $platform = null;

	/**
	 * @return \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	protected function getDoctrineDbalPlatform()
	{
		if( is_null($this->platform))
		{
			// detect platform
			$driver = \DB::connection()->getDriverName();
			switch($driver) {
				case "mysql": $this->platform = new MySqlPlatform(); break;
				default:
					throw new \InvalidArgumentException("SQL driver '{$driver}' is not supported");
			}
		}

		return $this->platform;
	}
}