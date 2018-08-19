<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:23
 */

namespace EApp\Cache\Database;

use EApp\Cache\KeyName;

class DatabaseKeyName extends KeyName
{
	public function getKeyName(): string
	{
		$key_name = $this->name;
		if( count($this->data) ) $key_name .= "?" . http_build_query($this->data);
		return strlen($key_name) > 255 ? md5($key_name) : $key_name;
	}

	public function getKeyPrefix(): string
	{
		return strlen($this->prefix) > 255 ? md5($this->prefix) : $this->prefix;
	}
}