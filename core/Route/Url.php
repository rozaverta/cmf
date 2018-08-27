<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 16:44
 */

namespace EApp\Route;

use EApp\Component\Context;
use EApp\Interfaces\CreateInstanceInterface;
use EApp\Prop;
use EApp\Support\Regexp;
use EApp\Support\Str;
use EApp\Traits\SingletonInstanceTrait;

/**
 * Class Url
 *
 * @package CI
 * @method static Url getInstance()
 */
class Url implements \Countable, CreateInstanceInterface
{
	use SingletonInstanceTrait;

	protected $url          = "";
	protected $base         = "/";
	protected $host         = "localhost";
	protected $port         = 80;
	protected $path         = "/";
	protected $is_dir       = true;
	protected $ext          = "";
	protected $lower_ext    = false;
	protected $segments     = [];
	protected $length       = 0;
	protected $dir_length   = 0;
	protected $context      = "/";
	protected $protocol     = "http";

	private $lower          = false;
	private $last           = false;
	private $mode           = 'get';
	private $base_path      = 'auto';
	private $base_prefix    = "";
	private $ready          = false;

	/**
	 * @var Prop
	 */
	private $config;

	protected function __construct( $prop = null )
	{
		$prop = is_array($prop) ? new Prop($prop) : Prop::cache('url');

		if( $prop->getIs("directory") )
		{
			$prefix = trim( $prop["directory"], " \t/" );
			if( strlen($prefix) )
			{
				$this->base_prefix = "/" . $prefix . "/";
			}
		}

		if( $prop->equiv('mode', 'rewrite') )
		{
			$this->mode = 'rewrite';
		}

		if( $prop->equiv("lower", true) )
		{
			$this->lower = true;
		}

		if( $prop->getIs("base") )
		{
			$this->base_path = $prop->modify("base", "lower", "trim");
		}

		$this->config = $prop;
	}

	/**
	 * @param array ...$args
	 * @return Url
	 */
	public static function createInstance( ... $args )
	{
		$len  = count($args);
		$prop = null;
		$path = "";

		if( $len > 0 )
		{
			if( is_array($args[0]) )
			{
				$prop = $args[0];
				if( $len > 1 && is_string($args[1]) )
				{
					$path = $args[1];
				}
			}
			else if( is_string($args[0]) )
			{
				$path = $args[0];
			}
		}

		$instance = new self($prop);
		if(strlen($path))
		{
			$instance->reload($path);
		}

		return $instance;
	}

	public function reloadRequest()
	{
		// host

		$host = (defined("BASE_PROTOCOL") ? BASE_PROTOCOL : "http") . "://";

		if( defined("ORIGINAL_HOST") )
		{
			$host .= ORIGINAL_HOST;
		}
		else if( isset($_SERVER['HTTP_HOST']) )
		{
			$host .= $_SERVER['HTTP_HOST'];
		}
		else
		{
			$host .= 'localhost';
		}

		if( isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) !== 80 )
		{
			$host .= ':' . $_SERVER['SERVER_PORT'];
		}

		// path

		$path = '';

		if( $this->mode === 'get' )
		{
			if( ! empty($_GET['q']) )
				$path .= $_GET['q'];

			$path = preg_replace('|/{2,}|', '', $path);
		}
		else if( isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) )
		{
			$path = trim($_SERVER['REQUEST_URI']);
			$pos = strpos( $path, "?" );

			if( $pos !== false )
			{
				$path = substr( $path, 0, $pos );
			}

			$path = rawurldecode($path);
			if( strpos($path, $_SERVER['SCRIPT_NAME']) === 0 )
			{
				$path = (string) substr($path, strlen($_SERVER['SCRIPT_NAME']));
			}
			else if( strpos($path, dirname($_SERVER['SCRIPT_NAME'])) === 0 )
			{
				$path = (string) substr($path, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}

		$path = ltrim( $path, "/" );
		if( $path !== '' )
		{
			$path = $this->cleanRelative( $path );
		}

		$this->reload( $host . "/" . $path );

		return $this;
	}

	public function reload( string $path )
	{
		// clean data

		$this->context    = "/";
		$this->ext        = "";
		$this->lower_ext  = "";
		$this->segments   = [];
		$this->length     = 0;
		$this->dir_length = 0;
		$this->last       = "";

		// set mode

		if( $this->ready )
		{
			$this->mode = "user";
		}
		else
		{
			$this->ready = true;
		}

		$host_default = defined("ORIGINAL_HOST") ? ORIGINAL_HOST : ($_SERVER['HTTP_HOST'] ?? "localhost");
		$host_protocol = defined("BASE_PROTOCOL") ? BASE_PROTOCOL : "http";

		// add host prefix ?

		if( ! preg_match('/^[a-z]{3,8}:/', $path) )
		{
			$prefix = $host_protocol;
			if( substr($path, 0, 2) === "//" )
			{
				$path = $prefix . ":" . $path;
			}
			else
			{
				$prefix .= "://";
				$prefix .= $host_default;

				if( isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) !== 80 )
				{
					$prefix .= ':' . $_SERVER['SERVER_PORT'];
				}

				if( ! strlen($path) || $path[0] !== "/" )
				{
					$path = "/" . $path;
				}

				$path = $prefix . $path;
			}

		}

		$len = strlen($this->base_prefix);
		$parse = parse_url( $path );
		if( ! $parse )
		{
			$parse = [
				"scheme" => $host_protocol,
				"host" => $host_default,
				"path" => $path
			];
		}

		$this->host = $parse["host"] ?? $host_default;
		$this->port = isset($parse["port"]) ? intval($parse["port"]) : 80;
		$this->protocol = $parse["scheme"] ?? $host_protocol;
		$this->path = $parse["path"] ?? "/";
		$this->url = $this->protocol . "://" . $this->host;
		$this->base = $this->base_path == "full" ? $this->url : "";

		if( ! strlen($this->path) || $this->path[0] !== "/" )
		{
			$this->path = "/" . $this->path;
		}

		$this->is_dir = strrpos( $this->path, "/" ) === strlen( $this->path ) - 1;

		if( $len > 0 )
		{
			$this->url  .= $this->base_prefix;
			$this->base .= $this->base_prefix;

			$path = $this->path;
			if( ! $this->is_dir )
			{
				$path .= "/";
			}

			if( substr($path, 0, $len) === $this->base_prefix )
			{
				$this->path = $len < strlen($this->path) ? substr($this->path, $len) : "/";
			}
		}
		else
		{
			$this->url  .= "/";
			$this->base .= "/";
		}

		if( $this->path !== "/" )
		{
			if( $this->lower )
			{
				$this->path = Str::lower($this->path);
			}

			$path = trim( $this->path, "/" );
			$this->segments = explode( "/", $path );
			$this->length  = count( $this->segments );
			$this->url .= $path;

			if( $this->is_dir )
			{
				$this->url .= "/";
			}
			else if( preg_match( '|\.([a-z0-9]+)$|i', $this->path, $m ) )
			{
				$ext = "." . $m[1];
				$this->ext = $ext;
				$this->lower_ext = strtolower($ext);

				$last = $this->segments[$this->length-1];
				$this->last = substr( $last, 0, strlen($last) - strlen($ext) );
			}
			else
			{
				$this->last = $this->segments[$this->length-1];
			}
		}

		$this->dir_length = $this->length;
		$this->is_dir || -- $this->dir_length;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getBase(): string
	{
		return $this->base;
	}

	/**
	 * @return string
	 */
	public function getPrefix(): string
	{
		$prefix  = $this->protocol . "://" . $this->host;
		$prefix .= $this->isBasePrefix() ? $this->base_prefix : "/";
		return $prefix;
	}

	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * @param bool $context
	 * @return string
	 */
	public function getPath( bool $context = false ): string
	{
		if($context && $this->isContext())
		{
			return $this->context . substr($this->path, 1);
		}
		else
		{
			return $this->path;
		}
	}

	/**
	 * @return string
	 */
	public function getContext(): string
	{
		return $this->context;
	}

	/**
	 * @return bool
	 */
	public function isContext(): bool
	{
		return strlen($this->context) > 1;
	}

	/**
	 * @return bool
	 */
	public function isDir(): bool
	{
		return $this->is_dir;
	}

	/**
	 * @return string
	 */
	public function getExt(): string
	{
		return $this->ext;
	}

	/**
	 * @return string
	 */
	public function getLowerExt(): string
	{
		return $this->lower_ext;
	}

	/**
	 * @return string
	 */
	public function getProtocol(): string
	{
		return $this->protocol;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->protocol === "https";
	}

	/**
	 * @param int $number
	 * @return bool
	 */
	public function hasSegment( int $number ): bool
	{
		return $number >= 0 && $number < $this->length;
	}

	/**
	 * @param int $number
	 * @return string
	 */
	public function getSegment( int $number = 0 ): string
	{
		if( $number < 0 )
		{
			$number = $this->length + $number;
			if( $number < 0 )
			{
				return "";
			}
		}

		return $number < $this->length ? $this->segments[$number] : "";
	}

	/**
	 * @return array
	 */
	public function getSegments(): array
	{
		return $this->segments;
	}

	/**
	 * @return int
	 */
	public function getDirLength(): int
	{
		return $this->dir_length;
	}

	/**
	 * @return string
	 */
	public function getMode(): string
	{
		return $this->mode;
	}

	/**
	 * @return Prop
	 */
	public function getConfig(): Prop
	{
		return $this->config;
	}

	/**
	 * Get full url scheme string
	 *
	 * @param Context $context
	 * @param string $path
	 * @param array $query
	 * @return string
	 */
	public function makeContextURL( Context $context, $path = '', array $query = [] ): string
	{
		if( $context->isHost() )
		{
			$url  = $context->isSsl() ? "https://" : "http://";
			$url .= $context->getHost();
			$port = $context->getPort();
			if( $port > 0 && $port !== 80 )
			{
				$url .= ":" . $port;
			}
			$url .= $this->isBasePrefix() ? $this->base_prefix : "/";
		}
		else
		{
			$url = $this->getPrefix();
		}

		if( $context->isQuery() )
		{
			foreach($context->getQuery() as $name => $value)
			{
				if( is_array($value) )
				{
					if( ! isset($query[$name]) || ! in_array($query[$name], $value) )
					{
						$query[$name] = current($value);
					}
				}
				else
				{
					$query[$name] = $value;
				}
			}
		}

		if( is_array($path) )
		{
			$path = implode("/", $path);
		}
		else if( strlen($path) && $path[0] === "/" )
		{
			$path = substr($path, 1);
		}

		if( $context->isPath() )
		{
			$path = trim($context->getPath(), "/") . "/" . $path;
		}

		return $this->getModeURL($url, $path, $query);
	}

	/**
	 * Get url string
	 *
	 * @param string $path
	 * @param array $query
	 * @param bool $context
	 * @param bool|null $full
	 * @return string
	 */
	public function makeURL( $path = '', array $query = [], bool $context = false, bool $full = null ): string
	{
		if( is_null($full) )
		{
			$url = $this->base;
		}
		else if( $full )
		{
			$url = $this->getPrefix();
		}
		else
		{
			$url = $this->isBasePrefix() ? $this->base_prefix : "/";
		}

		if( is_array($path) )
		{
			$path = implode('/', $path);
		}
		else if( strlen($path) && $path[0] === "/" )
		{
			$path = substr($path, 1);
		}

		if( $context && $this->isContext() )
		{
			if( strlen($path) )
			{
				$path = substr($this->context, 1) . $path;
			}
			else
			{
				$path = trim($this->context, "/");
			}
		}

		return $this->getModeURL($url, $path, $query);
	}

	public function shift( $delta = 1 )
	{
		$delta = (int) $delta;

		if( $delta > 0 && $this->dir_length )
		{
			if( $delta > $this->dir_length )
			{
				$delta = $this->dir_length;
			}

			if( $delta == 1 )
			{
				$this->context .= array_shift($this->segments) . "/";
			}
			else
			{
				$this->context .= implode("/", array_splice($this->segments, 0, $delta)) . "/";
			}

			$this->length -= $delta;
			$this->dir_length -= $delta;
		}

		return $this;
	}

	public function equivExt( $ext ): bool
	{
		if( ! $this->lower_ext || ! $ext )
		{
			return false;
		}

		if( is_array($ext) )
		{
			for( $i = 0, $len = count($ext); $i < $len; $i++ )
			{
				if( $this->equivExt( (string) $ext[$i] ) )
				{
					return true;
				}
			}
			return false;
		}

		$ext = strtolower( $ext );
		if( $ext[0] !== "." )
		{
			$ext = "." . $ext;
		}

		return $this->lower_ext === $ext;
	}

	public function equivSegment( string $test, int $number = 0 ): bool
	{
		if( ! isset( $this->segments[$number] ) )
		{
			return false;
		}

		if( $this->lower )
		{
			$test = Str::lower($test);
		}

		return $this->segments[$number] === $test;
	}

	public function equivNumeric( int $number = 0 ) : bool
	{
		if( ! isset( $this->segments[$number] ) )
		{
			return false;
		}
		else
		{
			$segment = $this->segments[$number];
			return is_numeric($segment) && $segment > 0 && $segment[0] !== "0";
		}
	}

	public function equivThen( array $segments, $is_dir = false )
	{
		$length = count($segments);
		if( $this->length !== $length || $is_dir !== $this->is_dir )
		{
			return false;
		}

		for( $i = 0; $i < $length; $i++ )
		{
			$segment = $segments[$i];

			if( $segment instanceof \Closure ) $test = $segment($this->segments[$i]);
			else if( $segment instanceof Regexp ) $test = $segment->match($this->segments[$i]);
			else if( is_scalar($segment) ) $test = $this->equivSegment((string) $segment, $i);
			else $test = false;

			if( !$test )
			{
				return false;
			}
		}

		return true;
	}

	public function getLast( bool $suffix = false ): string
	{
		if( $this->last )
		{
			return $this->last . ( $suffix ? $this->ext : "" );
		}
		else
		{
			return "";
		}
	}

	/**
	 * @return bool
	 */
	public function isLower(): bool
	{
		return $this->lower;
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return $this->length;
	}

	// protected

	protected function cleanRelative( $uri )
	{
		$uris = array();
		$tok = strtok( $uri, '/' );
		while( $tok !== false )
		{
			if (( ! empty($tok) OR $tok === '0') && $tok !== '..' )
			{
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}

		return implode('/', $uris);
	}

	protected function isBasePrefix(): bool
	{
		return strlen($this->base_prefix) > 0;
	}

	protected function getModeURL( $url, $path, array $query ): string
	{
		if( strlen($path) && $path !== "/" )
		{
			if( $this->config->getOr("mode", "get") === 'get' )
			{
				$query['q'] = $path;
			}
			else
			{
				$url .= $path;
			}
		}

		if( count($query) )
		{
			$url .= '?' . http_build_query($query);
		}

		return $url;
	}
}