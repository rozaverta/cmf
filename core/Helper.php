<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 22:09
 */

namespace EApp;

use EApp\Support\Arr;
use EApp\Support\Collection;
use EApp\Support\HigherOrderTapProxy;

class Helper
{
	public static function isOsWindows(): bool
	{
		return strtolower(substr(PHP_OS, 0, 3)) === 'win';
	}

	public static function isSystemInstall( bool $reload = false ): bool
	{
		if( ! self::isSystemHost() )
		{
			return false;
		}

		if( defined("SYSTEM_INSTALL") )
		{
			return SYSTEM_INSTALL;
		}

		if( $reload )
		{
			$data = Prop::file("system");
			return (bool) $data["install"] ?? false;
		}
		else
		{
			return (bool) Prop::cache("system")->getOr("install", false );
		}
	}

	public static function isSystemHost(): bool
	{
		return defined("APP_HOST") && defined("APP_DIR");
	}

	public static function debug()
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

	public static function path( $path, $ext = false, array $extended = [] )
	{
		$prefix = CORE_DIR;

		if( $path[0] === "@" && ( $pos = strpos( $path, ":" ) ) ) {

			$name = substr( $path, 1, $pos-1 );
			if( isset($extended[$name]) )
			{
				$prefix = $extended[$name];
			}
			else
			{
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

		$path = $prefix . ltrim( $path, DIRECTORY_SEPARATOR );
		if( $path === false || $path === "/" )
		{
			return $path . DIRECTORY_SEPARATOR;
		}

		if( $ext === true )
		{
			$ext = ".php";
		}
		else if( is_string($ext) )
		{
			$ext = (string) $ext;
			if(strlen($ext) && $ext[0] !== ".")
			{
				$ext = "." . $ext;
			}
		}

		if( !$ext || strrpos($path, $ext) === strlen($path) - strlen($ext) )
		{
			return $path;
		}

		return $path . $ext;
	}

	/**
	 * @param string $file
	 * @param null|array $extract
	 * @param bool $fatal
	 * @param bool $once
	 */
	public static function includeFile( string $file, $extract = null, $fatal = false, $once = false )
	{
		if( is_array($extract) )
		{
			extract($extract, EXTR_REFS);
		}

		if( $fatal )
		{
			if( $once )
			{
				/** @noinspection PhpIncludeInspection */
				require_once $file;
			}
			else
			{
				/** @noinspection PhpIncludeInspection */
				require $file;
			}
		}
		else if( $once )
		{
			/** @noinspection PhpIncludeInspection */
			include_once $file;
		}
		else
		{
			/** @noinspection PhpIncludeInspection */
			include $file;
		}
	}

	/**
	 * @param $file
	 * @param string $getName
	 * @param mixed $empty_result
	 * @return mixed
	 */
	public static function includeImport( $file, $getName = 'data', $empty_result = "" )
	{
		/** @noinspection PhpIncludeInspection */
		include $file;
		return $getName && isset( ${$getName} ) ? ${$getName} : $empty_result;
	}

	/**
	 * Call the given Closure with the given value then return the value.
	 *
	 * @param  mixed  $value
	 * @param  callable|null  $callback
	 * @return mixed
	 */
	public static function tap($value, $callback = null)
	{
		if(is_null($callback))
		{
			return new HigherOrderTapProxy($value);
		}
		$callback($value);
		return $value;
	}

	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function value($value)
	{
		return $value instanceof \Closure ? $value() : $value;
	}

	/**
	 * GetTrait an item from an array or object using "dot" notation.
	 *
	 * @param  mixed   $target
	 * @param  string|array  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function dataGet($target, $key, $default = null)
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
					$target = $target->getAll();
				}
				else if(! is_array($target))
				{
					return self::value($default);
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
				return self::value($default);
			}
		}

		return $target;
	}
}