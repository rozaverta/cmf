<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:23
 */

namespace EApp\Cache;

class DatabaseHash extends Hash
{
	protected $delimiter = "/";

	protected $db_name;

	protected $db_prefix;

	public function keyName(): string
	{
		if( isset($this->db_name) )
		{
			return $this->db_name;
		}

		$key_name = $this->name;
		if( count($this->data) )
		{
			$key_name .= "?" . http_build_query($this->data);
		}

		$this->db_name = strlen($key_name) > 255 ? md5($key_name) : $key_name;
		return $this->db_name;
	}

	public function keyPrefix(): string
	{
		if( isset($this->db_prefix) )
		{
			return $this->db_prefix;
		}

		$len = strlen($this->prefix);
		if( $len < 255 )
		{
			return $this->prefix;
		}

		$start = 0;
		do {
			$pos = strpos($this->prefix, $start);
			if( $pos === false || $pos + 37 > 255 )
			{
				break;
			}
			else
			{
				$start = $pos;
			}
		}
		while( $pos !== false );

		if( $start < 1 )
		{
			return md5($this->prefix);
		}

		$prefix = substr($this->prefix, 0, ++ $pos);
		$suffix = md5(substr($this->prefix, $pos));
		$this->db_prefix = $prefix . "md5_" . $suffix;

		return $this->db_prefix;
	}
}