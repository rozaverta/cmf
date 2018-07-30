<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.12.2017
 * Time: 15:03
 */

namespace EApp\SecurityFilter;

use EApp\SecurityFilter\Exceptions\FilterException;
use EApp\SecurityFilter\Traits\FilterTrait;

class Filter
{
	use FilterTrait;

	public function filterMap( $value, $filters, $name = null )
	{
		if( is_string($filters) )
		{
			$filters = explode(":", $filters);
		}

		if( ! is_array($filters) )
		{
			throw new FilterException( $name, "Filter must be a string or an array" );
		}

		$keys = array_keys($filters);
		$count = count($keys);

		for( $i = 0; $i < $count; $i++ )
		{
			if( $i !== $keys[$i] )
			{
				$filters = [$filters];
				$count = 1;
				break;
			}
		}

		for( $i = 0; $i < $count; $i++ )
		{
			$flt = $filters[$i];
			if( is_string($flt) )
			{
				if( strpos($flt, ':') !== false )
				{
					foreach( explode(":", $flt) as $item )
					{
						$value = $this->filter($value, ["name" => $item], $name);
					}
				}
				else
				{
					$value = $this->filter( $value, ["name" => $flt], $name );
				}
			}
			else
			{
				$value = $this->filter( $value, $flt, $name );
			}
		}

		return $value;
	}
}