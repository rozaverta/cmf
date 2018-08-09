<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2017
 * Time: 13:50
 */

namespace EApp;

if( ! defined("BASE_DIR") ) {
	throw new \Exception("BASE_DIR is not defined");
}

$prop = Prop::cache('json');
$decode_options = 0;
$encode_options = 0;

if( $prop->isInt('decode_options') )
{
	$decode_options = $prop->get('decode_options');
}
else if( $prop->equiv('bigint_as_string', true) )
{
	$decode_options = JSON_BIGINT_AS_STRING;
}

if( $prop->isInt('encode_options') )
{
	$encode_options = $prop->get('encode_options');
}
else
{
	$valid = [
		'hex_quot' => JSON_HEX_QUOT,
		'hex_tag' => JSON_HEX_TAG,
		'hex_amp' => JSON_HEX_AMP,
		'hex_apos' => JSON_HEX_APOS,
		'numeric_check' => JSON_NUMERIC_CHECK,
		'pretty_print' => JSON_PRETTY_PRINT,
		'unescaped_slashes' => JSON_UNESCAPED_SLASHES,
		'force_object' => JSON_FORCE_OBJECT,
		'unescaped_unicode' => JSON_UNESCAPED_UNICODE
	];

	foreach( $valid as $name => $opt )
	{
		if( $prop->equiv($name, true) )
		{
			$encode_options = $encode_options | $opt;
		}
	}

	if( ! $prop->equiv('unescaped_unicode', false) )
	{
		$encode_options = $encode_options | JSON_UNESCAPED_UNICODE;
	}
}

define("JSON_DECODE_OPTIONS", $decode_options);
define("JSON_ENCODE_OPTIONS", $encode_options);

namespace EApp\Database;

use PDO;

const FETCH_ASSOC = PDO::FETCH_ASSOC;
const FETCH_OBJ = PDO::FETCH_OBJ;
const FETCH_BOUND = PDO::FETCH_BOUND;
const FETCH_COLUMN = PDO::FETCH_COLUMN;
const FETCH_NUM = PDO::FETCH_NUM;

const QUERY_READ   = 1;
const QUERY_WRITE  = 2;
const QUERY_SELECT = 3;
const QUERY_INSERT = 4;
const QUERY_UPDATE = 5;
const QUERY_DELETE = 6;

namespace E;

use EApp\Support\Arr;
use EApp\Support\Collection;
use EApp\Support\HigherOrderTapProxy;

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
function Value($value)
{
	return $value instanceof \Closure ? $value() : $value;
}

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
 * @return mixed
 */
function Tap($value, $callback = null)
{
	if(is_null($callback))
	{
		return new HigherOrderTapProxy($value);
	}
	$callback($value);
	return $value;
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param  mixed   $target
 * @param  string|array  $key
 * @param  mixed   $default
 * @return mixed
 */
function DataGet($target, $key, $default = null)
{
	if( is_null($key) )
	{
		return $target;
	}

	if( !is_array($key) )
	{
		$key = explode(".", $key);
	}

	while (! is_null($segment = array_shift($key)))
	{
		if ($segment === '*')
		{
			if($target instanceof Collection)
			{
				$target = $target->all();
			}
			else if(! is_array($target))
			{
				return Value($default);
			}

			$result = Arr::pluck($target, $key);
			return in_array('*', $key) ? Arr::collapse($result) : $result;
		}

		if(Arr::accessible($target) && Arr::exists($target, $segment))
		{
			$target = $target[$segment];
		}
		else if(is_object($target) && isset($target->{$segment}))
		{
			$target = $target->{$segment};
		}
		else
		{
			return Value($default);
		}
	}

	return $target;
}

function Debug()
{
	$num = func_num_args();
	$get = '';
	$dmp = defined("DEBUG_VAR_DUMP") && DEBUG_VAR_DUMP === true;

	$len = ob_get_length();
	if( $len )
	{
		while( $len > 0 )
		{
			if( $num < 1 ) $get .= ob_get_contents();
			@ ob_end_clean();
			$len --;
		}
	}

	headers_sent() || header('Content-Type: text/plain; charset=utf-8');
	if( $num > 0 )
	{
		foreach( func_get_args() as $data )
		{
			$dmp ? var_dump($data) : print_r($data);
		}
	}
	else
	{
		echo $get;
	}

	exit;
}

function Path( $path, $isDir = false, array $extended = [] )
{
	$prefix = CORE_DIR;
	if( $path[0] === "@" && ( $pos = strpos( $path, ":" ) ) ) {

		$name = substr( $path, 1, $pos-1 );
		if( isset($extended[$name]) ) {
			$prefix = $extended[$name];
		}
		else {
			switch( $name ) {
				case "app" :
				case "application" : $prefix = APP_DIR; break;
				case "assets" : $prefix = ASSETS_DIR; break;
				case "template" :
				case "view" : $prefix = APP_DIR . "view" . DIRECTORY_SEPARATOR; break;
				case "config" : $prefix = APP_DIR . "config" . DIRECTORY_SEPARATOR; break;
				case "cache" : $prefix = APP_DIR . "cache" . DIRECTORY_SEPARATOR; break;
				case "core" : $prefix = CORE_DIR; break;
				case "base" :
				default : $prefix = BASE_DIR; break;
			}
		}

		$path = substr( $path, $pos + 1 );
	}
	else if( $path[0] === ':' )
	{
		$prefix = '';
		$path = substr( $path, 1 );
	}

	if( DIRECTORY_SEPARATOR !== "/" )
	{
		$path = str_replace( "/", DIRECTORY_SEPARATOR, $path );
	}

	return $prefix . ltrim( $path, DIRECTORY_SEPARATOR ) . ( $isDir ? DIRECTORY_SEPARATOR : ".php" );
}

function IncludeFile( string $file, $extract = null, $fatal = false, $once = false )
{
	if( is_array($extract) )
	{
		extract($extract, EXTR_REFS);
	}

	if( $fatal )
	{
		if( $once )
		{
			require_once $file;
		}
		else
		{
			require $file;
		}
	}
	else if( $once )
	{
		include_once $file;
	}
	else
	{
		include $file;
	}
}

/**
 * @param $file
 * @param string $getName
 * @param string $empty_result
 * @return array|mixed
 */
function IncludeContentFile( $file, $getName = 'data', $empty_result = "" )
{
	/** @noinspection PhpIncludeInspection */
	include $file;
	return $getName && isset( ${$getName} ) ? ${$getName} : $empty_result;
}
