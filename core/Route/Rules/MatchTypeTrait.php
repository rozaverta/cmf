<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 18:25
 */

namespace EApp\Route\Rules;


trait MatchTypeTrait
{
	/**
	 * @param string $value
	 * @param string $type
	 * @param $properties
	 * @param null $match
	 * @return bool
	 */
	protected function matchBase( string $value, string $type, $properties, & $match = null ): bool
	{
		static $dates =
			[
				"date" => "d-m-Y",
				"date_time" => "d-m-Y-H-i-s",
				"date_hour" => "d-m-Y-H",
				"date_hour_minute" => "d-m-Y-H-i",
				"date_year" => "Y",
				"date_month_year" => "m-Y"
			];

		switch($type)
		{
			case "all":
				return true;
				break;

			case "alpha":
				return ! preg_match('/[^a-zA-Z]/', $value);
				break;

			case "number":
				if(is_numeric($value) && $value > 0 && $value[0] !== "0")
				{
					$match = (int) $value;
					return true;
				}
				break;

			case "alpha_number":
				return ! preg_match('/[^a-zA-Z0-9]/', $value);
				break;

			case "enum":
				return is_array($properties) ? in_array($value, $properties, true) : ($properties === $value);
				break;

			case "regexp":
				if( is_string($properties) && strlen($properties) > 0 && preg_match($properties, $value, $m) )
				{
					if( count($m) > 1 ) $match = $m;
					return true;
				}
				break;

			case "date_format":
				if(is_string($properties) && strlen($properties) > 0)
				{
					return $this->matchDate($value, $properties, $match);
				}
				break;

			default:
				if(isset($dates[$type]))
				{
					return $this->matchDate($value, $dates[$type], $match);
				}
				break;
		}

		return false;
	}

	/**
	 * @param string $value
	 * @param string $format
	 * @param $match
	 * @return bool
	 */
	private function matchDate( string $value, string $format, & $match ): bool
	{
		$date = \DateTime::createFromFormat($format, $value);

		if( $date && $date->format($format) === $value )
		{
			$match = $date;
			return true;
		}

		return false;
	}
}