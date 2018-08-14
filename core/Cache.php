<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:26
 */

namespace EApp;

use Closure;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Interfaces\Htmlable;
use EApp\Support\Interfaces\Jsonable;
use EApp\Support\Json;
use EApp\Support\Traits\Write;

/**
 * Class Cache
 */
class Cache
{
	use Write;

	private $prop = [];
	private $info = [];
	private $name = "";
	private $time = 0;
	private $directory = "";

	private $dir   = "";
	private $path  = false;
	private $ready = false;
	private $found = false;
	private $raw   = false;

	private $baseDir = "";

	private $cacheData = null;

	public function __construct( $baseName, $directory = null, array $data = null )
	{
		$this->name = trim( $baseName );
		$this->baseDir = ( defined("APP_DIR") ? (APP_DIR . "cache") : sys_get_temp_dir() ) . DIRECTORY_SEPARATOR;

		if( ! $this->validFileName($this->name) )
		{
			$this->name = md5( $this->name );
		}

		if( $directory )
		{
			$this->set( "directory", $directory );
		}

		if( is_array($data) )
		{
			$this->setData( $data );
		}
	}

	public function set( $name, $value )
	{
		if( $this->ready )
		{
			throw new \Exception("Cache is has been ready, you can't change properties");
		}

		// clean calculate path
		$this->path = false;

		if( $name === "time" )
		{
			$this->time = is_numeric( $value ) ? (int) $value : 0;
		}
		else if( $name === "directory" )
		{
			$value = trim($value, '/ ');
			if( !strlen($value) )
			{
				throw new \InvalidArgumentException("Empty cache directory name");
			}

			$private = $value[0] === ".";
			if( $private )
			{
				$value = substr($value,1);
			}

			$directories = [];
			$value = explode( "/", $value );

			foreach( $value as $dir_name )
			{
				if( !$this->validFileName($dir_name) )
				{
					throw new \InvalidArgumentException("Invalid directory name '{$dir_name}'");
				}
				$directories[] = $dir_name;
			}

			$this->directory = ( $private ? "." : "" ) . implode( DIRECTORY_SEPARATOR, $directories );
		}
		else if( $name === "raw" )
		{
			$this->raw = (bool) $value;
		}
		else
		{
			$this->prop[$name] = $value;
		}

		return $this;
	}

	public function setData( array $data )
	{
		foreach( $data as $name => $value )
		{
			$this->set( $name, $value );
		}
		return $this;
	}

	public function ready()
	{
		if( $this->ready )
		{
			return $this->found;
		}

		$this->ready = true;
		$this->found = false;

		$php = $this->getPath();
		if( ! file_exists( $php ) )
		{
			return false;
		}

		$txt = $this->getPath( true );
		if( file_exists( $txt ) )
		{
			$data = \E\IncludeContentFile($txt);
			if( isset( $data["expired"] ) && time() > $data["expired"] )
			{
				@ unlink( $php );
				@ unlink( $txt );
				return false;
			}
			else if( is_array($data) )
			{
				$this->info = $data;
			}
		}

		return $this->found = true;
	}

	public function sync( Closure $callback )
	{
		if( $this->ready() ) {
			return true;
		}

		try {
			$data = $callback();
			if( $data === false )
			{
				throw new \Exception("Can't load cache data");
			}
		}
		catch( \Exception $e )
		{
			App::Log()->exception($e);
			return false;
		}

		return $this->write($data);
	}

	public function writePhp( $data )
	{
		if($this->raw)
		{
			throw new \InvalidArgumentException("You cannot use writePhp method for raw file");
		}

		$file = $this->getPath();
		$this->makeDir( $this->baseDir );
		strlen($this->dir) && $this->makeDir( $this->dir );

		if( !$this->writeFileContent( $file, $data ) )
		{
			$this->cacheData = null;
			return false;
		}

		$this->info =
			[
				"created" => time()
			];

		if( $this->time )
		{
			$this->info["expired"] = $this->info["created"] + $this->time;
			$this->writeFileContent( $this->getPath( true ), $this->info );
		}

		$this->ready = true;
		$this->found = true;

		return true;
	}

	public function write( $data, $as_php = null )
	{
		if( !$this->raw )
		{
			if( ! is_string( $data ) && $as_php !== true )
			{
				$as_php = true;
			}

			if( $data instanceof Closure )
			{
				$data = $data();
			}

			if( ! $as_php )
			{
				$this->cacheData = $this->toString( $data );
				$data =
					"\nob_start(); ?>" .
					preg_replace_callback( '/<\?|\?>/', static function ( $m ) {
						if( $m[ 0 ] == '<?' )
						{
							return '<?= "<?" ?>';
						}
						else
						{
							return '<?= "?>" ?>';
						}
					}, $this->cacheData ) .
					'<' . "?php\n\n\$data = ob_get_contents();\nob_end_clean();\n";
			}
			else
			{
				if( !is_array( $data ) )
				{
					if( $data instanceof Arrayable )
					{
						$data = $data->toArray();
					}
					else if( is_object( $data ) )
					{
						$data = get_object_vars( $data );
					}
					else if( is_null( $as_php ) )
					{
						$data = $this->toString( $data );
					}
					else
					{
						throw new \InvalidArgumentException( "Invalid cache content type '" . gettype( $data ) . "'" );
					}
				}

				$this->cacheData = $data;
			}
		}
		else if( !( $data instanceof Closure ) )
		{
			$convert = is_string( $as_php ) ? strtolower( $as_php ) : ( $as_php === true ? "php" : "plain" );
			if( $convert === "json" )
			{
				if( $data instanceof Jsonable )
				{
					$data = $data->toJson();
				}
				else
				{
					if( $data instanceof Arrayable )
					{
						$data = $data->toArray();
					}

					$data = Json::stringify( $data );
				}
			}
			else if( !is_string( $data ) )
			{
				if( ($convert == "xml" || $convert == "html") && $data instanceof Htmlable )
				{
					$data = $data->toHtml($convert == "xml");
				}
				else if( $convert === "php" )
				{
					$data = App::PhpExport()->data( $data );
				}
				else
				{
					$data = $this->toString( $data );
				}
			}
		}

		$file = $this->getPath();
		$this->makeDir( $this->baseDir );
		strlen($this->dir) && $this->makeDir( $this->dir );

		if( !$this->writeFileContent( $file, $data ) )
		{
			$this->cacheData = null;
			return false;
		}

		$this->info =
			[
				"created" => time()
			];

		if( $this->time )
		{
			$this->info["expired"] = $this->info["created"] + $this->time;
		}

		if( $this->raw )
		{
			$this->info["size"] = filesize($file);
		}

		if( $this->time || $this->raw )
		{
			$this->writeFileContent( $this->getPath( true ), $this->info );
		}

		$this->ready = true;
		$this->found = true;

		return true;
	}

	public function getContentData()
	{
		if( ! $this->ready() )
		{
			return false;
		}
		else if( isset($this->cacheData) )
		{
			return $this->cacheData;
		}
		else
		{
			return \E\IncludeContentFile($this->getPath());
		}
	}

	public function readFile( $mime_type = null, array $flags = [] )
	{
		if( !$this->ready() )
		{
			throw new \Exception("Cache is not loaded");
		}

		App::Response()->file( $this->path(), $mime_type, $flags );
	}

	public function created()
	{
		if( !$this->ready() )
		{
			return false;
		}

		if( !isset($this->info["created"]) )
		{
			$this->info["created"] = filemtime($this->path());
		}

		return $this->info["created"];
	}

	public function size()
	{
		if( !$this->ready() )
		{
			return 0;
		}

		if( !isset($this->info["size"]) )
		{
			$this->info["size"] = filesize($this->path());
		}

		return $this->info["size"];
	}

	public function expired()
	{
		if( $this->ready() )
		{
			return isset($this->info["expired"]) ? $this->info["expired"] : false;
		}

		if( $this->time < 1 )
		{
			return false;
		}
		else
		{
			return time() + $this->time;
		}
	}

	public function path()
	{
		return $this->getPath();
	}

	public function clean()
	{
		if( $this->ready() )
		{
			$php = $this->getPath();
			$txt = $this->getPath(true);

			if( file_exists( $php ) )
			{
				@ unlink($php);
			}

			if( file_exists( $txt ) )
			{
				@ unlink( $txt );
			}

			$this->found = false;
			$this->cacheData = null;
		}
	}

	private function getPath( $info = false )
	{
		if( ! $this->path )
		{
			$this->path = $this->baseDir;
			if( $this->directory )
			{
				$this->path .= $this->directory;
				$this->dir   = $this->path;
				$this->path .= DIRECTORY_SEPARATOR;
			}

			if( count($this->prop) )
			{
				$this->path .= $this->name;
				$this->dir   = $this->path;

				$name = [];
				foreach( $this->prop as $key => $value )
				{
					$name[] = $key . '-' . $value;
				}
				$name = implode('_', $name);
				if( ! $this->validFileName($name) )
				{
					$name = md5($name);
				}

				$this->path .= DIRECTORY_SEPARATOR . $name;
			}
			else
			{
				$this->path .= $this->name;
			}
		}

		if( $info )
		{
			return $this->path . ".info.php";
		}
		else if( $this->raw )
		{
			return $this->path . ".raw";
		}
		else
		{
			return $this->path . ".php";
		}
	}

	private function validFileName($name)
	{
		$len = strlen($name);
		return $len > 0 && $len <= 64 && ! preg_match('/[^a-zA-Z0-9_\-]/', $name);
	}

	private function toString($data)
	{
		if( is_string($data) )
		{
			return $data;
		}

		if( is_scalar($data) || is_object($data) && method_exists($data, '__toString') )
		{
			return $data . "";
		}
		else
		{
			throw new \InvalidArgumentException("Invalid cache content type '" . gettype($data) . "'");
		}
	}
}