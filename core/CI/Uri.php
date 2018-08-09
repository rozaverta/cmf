<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 16:44
 */

namespace EApp\CI;

use EApp\App;
use EApp\Prop;
use EApp\Support\Traits\SingletonInstance;

/**
 * Class Uri
 *
 * @package CI
 * @method static Uri getInstance()
 */
class Uri
{
	use SingletonInstance;

	public $url       = "";
	public $base      = "/";
	public $host      = "";
	public $port      = 80;
	public $path      = "/";
	public $isDir     = true;
	public $ext       = "";
	public $segment   = [];
	public $length    = 0;
	public $dirLength = 0;
	public $router    = "/";

	private $extLower = false;
	private $lower    = false;
	private $last     = false;
	private $mode     = 'get';
	private $basePath = 'auto';

	private $load_host = "";
	private $load_directory = "";

	public function __construct()
	{
		$prop = Prop::cache('uri');
		$this->load_directory = "";

		if( $prop->getIs("directory") )
		{
			$this->load_directory = trim( $prop["directory"], " \t/" );
			if( $this->load_directory )
			{
				$this->load_directory .= "/";
			}
		}

		if( $prop->equiv('mode', 'rewrite') )
		{
			$this->mode = 'rewrite';
		}

		if( $prop->getIs('host') )
		{
			$this->load_host = $prop['host'];
		}
		else if( defined("APP_HOST") )
		{
			$this->load_host = APP_HOST;
		}
		else if( isset($_SERVER['HTTP_HOST']) )
		{
			$this->load_host = $_SERVER['HTTP_HOST'];
		}
		else
		{
			$this->load_host = 'localhost';
		}

		if( $prop->equiv("lower", true) )
		{
			$this->lower = true;
		}

		if( $prop->getIs("base") )
		{
			$this->basePath = $prop->modify("base", "lower", "trim");
		}

		$this->reload( $this->_request() );
	}

	public function reload( $path )
	{
		static $ready = false;

		// clean data

		$this->base      = "/";
		$this->host      = "";
		$this->port      = 80;
		$this->path      = "/";
		$this->ext       = "";
		$this->segment   = [];
		$this->length    = 0;
		$this->dirLength = 0;
		$this->router    = "/";
		$this->extLower  = false;
		$this->last      = false;

		// set mode

		if( $ready )
		{
			$this->mode = "user";

			$path = ltrim( $path, "/" );
			if( $path !== '' )
			{
				$this->isDir = strrpos( $path, "/" ) === strlen( $path ) - 1;
				$path = $this->_cleanRelative( $path );
			}
			else
			{
				$this->isDir = true;
			}
		}
		else
		{
			$ready = true;
		}

		// read path

		$parse = $this->load_host;
		if( isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) !== 80 )
		{
			$parse .= ':' . $_SERVER['SERVER_PORT'];
		}

		$this->url = BASE_PROTOCOL . "://" . $parse . "/" . $this->load_directory;
		$parse = parse_url( BASE_PROTOCOL . "://" . $parse . "/" . $path );

		foreach( ['host', 'port', 'path'] as $name )
		{
			if( isset( $parse[$name] ) )
			{
				$this->{$name} = $parse[$name];
			}
		}

		if( !$this->host )
		{
			$this->host = $this->load_host;
		}

		if( $this->path && $this->path !== "/" )
		{
			if( $this->lower )
			{
				$this->path = mb_strtolower( $this->path, BASE_ENCODING );
			}

			$this->segment = explode( "/", trim( $this->path, "/" ) );
			$this->length  = count( $this->segment );
			$this->url .= ltrim( $this->path, "/" );

			if( $this->isDir )
			{
				$this->url .= "/";
			}
			else if( preg_match( '|\.([a-z0-9]+)$|i', $this->path, $m ) )
			{
				$ext = "." . $m[1];
				$this->ext = $ext;
				$this->extLower = strtolower($ext);

				$last = $this->segment[ $this->length-1 ];
				$this->last = substr( $last, 0, strlen($last) - strlen($ext) );
			}
			else {
				$this->last = $this->segment[ $this->length-1 ];
			}
		}

		if( $this->basePath == "full" ) $this->base = BASE_PROTOCOL . "://" . $this->host . "/" . $this->load_directory;
		else if( $this->basePath == "relative" ) $this->base = $this->load_directory;
		else $this->base = "/" . $this->load_directory;

		$this->dirLength = $this->length;
		$this->isDir || --$this->dirLength;
		$this->isDir && $this->length && $this->path .= "/";
	}

	public function mode()
	{
		return $this->mode;
	}

	public function makeURL( $path = '', array $query = [], $router = false, $full = false )
	{
		$get = $this->base;
		if( $full ) {
			$get = BASE_PROTOCOL . '://' . $this->host . $get;
		}

		if( is_array($path) ) {
			$path = implode('/', $path);
		}

		$path = ltrim($path, '/');
		if( $router && $this->router !== '/' ) {
			$path = $this->router . $path;
		}

		if( $this->mode === 'get' ) {
			$query['page'] = $path;
		}
		else {
			$get .= $path;
		}

		if( count($query) ) {
			$get .= '?' . http_build_query($query);
		}

		return $get;
	}

	public function shift( $delta = 1 )
	{
		$delta = (int) $delta;

		if( $delta > 0 && $this->dirLength ) {

			if( $delta > $this->dirLength ) {
				$delta = $this->dirLength;
			}

			if( $delta == 1 ) {
				$this->router .= array_shift($this->segment) . "/";
			}
			else {
				$this->router .= implode("/", array_splice($this->segment, 0, $delta)) . "/";
			}

			if( $this->router[0] === '/' ) {
				$this->router = substr($this->router, 1);
			}

			$this->length -= $delta;
			$this->dirLength -= $delta;
		}

		return $this;
	}

	public function getSegment( $number = 0 )
	{
		if( $number < 0 )
		{
			$number = $this->length + $number;
			if( $number < 0 )
			{
				return false;
			}
		}

		return $number < $this->length ? $this->segment[$number] : false;
	}

	public function equivExt( $ext )
	{
		if( !$this->extLower || !$ext ) {
			return false;
		}

		if( is_array($ext) ) {
			for( $i = 0, $len = count($ext); $i < $len; $i++ ) {
				if( $this->equivExt( $ext[$i] ) ) {
					return true;
				}
			}
			return false;
		}

		$ext = strtolower( $ext );
		if( $ext[0] !== "." ) {
			$ext = "." . $ext;
		}

		return $this->extLower === $ext;
	}

	public function equivSegment( $test, $segmentNumber = 0 )
	{
		if( !isset( $this->segment[$segmentNumber] ) ) {
			return false;
		}
		if( $this->lower ) {
			$test = mb_strtolower( $test, BASE_ENCODING );
		}
		return $this->segment[$segmentNumber] === $test;
	}

	public function equivNumeric( $segmentNumber = 0, $last = false ) {
		if( !isset( $this->segment[$segmentNumber] ) ) {
			return false;
		}
		else {
			return is_numeric( $this->segment[$segmentNumber] ) && ( !$last || $segmentNumber+1 === $this->length );
		}
	}

	const MATCH_INDEX = 1;
	const MATCH_REG_EXP = 2;
	const MATCH_URI = 3;
	const MATCH_OF = 4;
	const MATCH_PATH = 5;
	const MATCH_QUERY = 6;
	const MATCH_HOST = 7;

	public function match( $type, $match = null, & $match_of = null )
	{
		static $types = [
			"INDEX" => self::MATCH_INDEX,
			"MATCH" => self::MATCH_REG_EXP,
			"URI" => self::MATCH_URI,
			"OF" => self::MATCH_OF,
			"PATH" => self::MATCH_PATH,
			"QUERY" => self::MATCH_QUERY,
			"HOST" => self::MATCH_HOST
		];

		if( !is_int($type) ) {
			$type = strtoupper($type);
			if( !isset($types[$type]) ) {
				return false;
			}
			$type = $types[$type];
		}

		if( $type === self::MATCH_INDEX )
		{
			return $this->length === 0;
		}

		if( $type === self::MATCH_HOST )
		{
			// protocol, host
			if( !preg_match('/^(?:(https?):\/{0,2})?(.*?)(?::(\d+))?$/', $match, $m) ||
				$m[2] !== APP_HOST ||
				isset($m[1]) && $m[1] !== BASE_PROTOCOL ) {
				return false;
			}
			// port
			if( isset($m[3]) && strlen($m[3]) ) {
				$port = isset($_SERVER["SERVER_PORT"]) ? (int) $_SERVER["SERVER_PORT"] : 80;
				if( $port !== intval($m[3]) ) {
					return false;
				}
			}
			return true;
		}

		if( $type === self::MATCH_REG_EXP )
		{
			return preg_match( $match, $this->path, $match_of );
		}

		if( $type === self::MATCH_URI )
		{
			if( !strlen($match) || $match[0] !== "/" ) {
				$match = "/" . $match;
			}
			return $this->path === $match;
		}

		if( $type === self::MATCH_OF )
		{
			$len = strlen($match);
			if( $len < 1 ) {
				return false;
			}

			$last = $match[$len-1];
			$end_all = $last == "~";

			if( $end_all ) {
				$match = substr($match, 0, $len - 1);
			}
			// check is dir or not
			else if( ($last === "/") !== $this->isDir ) {
				return false;
			}

			$match = trim($match, "/");
			if( !strlen($match) ) {
				return false;
			}

			$match = explode("/", $match);
			$length = count($match);

			if( $length > $this->length ) {
				return false;
			}

			// ** - all for multiply segments
			// * - all
			// ^ - start of
			// $ - end of
			// ? - is numeric
			// .[a-z] - end of ext
			// ~ end all
			// [0-9] - * repeat equivalent /*/ -> /1/ or /*/*/*/*/ -> /4/

			$m_of = [];

			for( $i = 0, $j = 0, $all = false; $i < $length && $j < $this->length; $i++ )
			{
				$end = $i + 1 === $length;
				$key = $match[$i];
				$len = strlen($key);

				if( !$len ) {
					return false;
				}

				$segment = $this->segment[$j++];

				if( ! $this->isDir && ! $end_all && $end && $j == $this->length && preg_match('/^(.*?)\.(.+?)$/', $key, $m) ) {
					if( !$this->equivExt($m[2]) ) {
						return false;
					}

					$segment = $this->last;
					$key = $m[1];
					$len = strlen($key);
					if( !$len ) {
						$key = "*";
						$len = 1;
					}
				}

				if( $len === 1 ) {
					// next
					if( $key == "*" ) {
						$m_of[] = $segment;
						continue;
					}

					// is number
					if( $key == "?" ) {
						if( preg_match('/[^0-9]/', $segment) ) {
							return false;
						}
						else {
							$m_of[] = (int) $segment;
						}
						continue;
					}
				}

				if( is_numeric($key) ) {
					$key = intval($key) - 1;
					if( $key > 0 && $j + $key < $this->length ) {
						$m_of[] = $segment;
						$k = $j;
						$j += $key;
						for( ; $k < $j; $k++ ) {
							$m_of[] = $this->segment[$k];
						}
						continue;
					}
					else {
						return false;
					}
				}

				if( $len === 2 && $key == "**" ) {
					$m_of[] = $segment;
					if( $end ) {
						$i = $length;
						for(; $j < $this->length; $j++ ) {
							$m_of[] = $this->segment[$j];
						}
						break;
					}
					else {
						$all = true;
						continue;
					}
				}

				$of = $key[0];
				$mk = false;
				$sg = $segment;

				if( $of === "^" || $of === "$" ) {
					$key = substr($key, 1);
					if( $len -- < 2 || strlen($segment) - $len < 1 ) {
						$sg = false;
					}
					else {
						$mk = $of === "^" ? substr($segment, $len) : substr($segment, 0, strlen($segment) - $len);
						$sg = $of === "^" ? substr($segment, 0, $len) : substr($segment, strlen($segment) - $len);
					}
				}

				if( $key[0] === "\\" ) {
					if( $len < 2 ) {
						return false;
					}
					else {
						$key = substr($key, 1);
					}
				}

				if( $this->lower ) {
					$key = mb_strtolower( $key, BASE_ENCODING );
				}

				if( $key !== $sg ) {
					if( $all ) {
						$i --;
					}
					else {
						return false;
					}
				}
				else if( $all ) {
					$all = false;
				}

				if( $mk !== false ) {
					$m_of[] = $mk;
				}
				else if( $all ) {
					$m_of[] = $segment;
				}
			}

			if( $i !== $length || $end_all && $j === $this->length && ! $this->isDir || ! $end_all && $j !== $this->length ) {
				return false;
			}

			$match_of = [
				"length" => $j,
				"match" => $m_of
			];

			return true;
		}

		if( $type === self::MATCH_PATH )
		{
			$match = explode("/", $match);
			$length = count($match);

			if( $length > $this->dirLength ) {
				return false;
			}

			$m_of = [];
			for( $i = 0; $i < $length; $i++ ) {
				if( $this->segment[$i] !== $match[$i] ) {
					return false;
				}
				else {
					$m_of[] = $match[$i];
				}
			}

			$match_of = [
				"length" => $length,
				"match" => $m_of
			];

			return true;
		}

		if( $type === self::MATCH_QUERY && is_array($match) && count($match) > 0 )
		{
			$inp = App::Request();
			$m_of = [];

			foreach( $match as $key => $value )
			{
				$get = $inp->get($key);
				if( $get === null || strlen($value) && $value !== $get )
				{
					return false;
				}
				else
				{
					$m_of[$key] = $get;
				}
			}

			$match_of = $m_of;

			return true;
		}

		return false;
	}

	public function equivRule( $segment, $ext, $isDir = false, $length = 0 )
	{
		if( !$this->length || $isDir !== $this->isDir || $length > 0 && $this->length !== $length ) {
			return false;
		}

		if( is_string($ext) && strlen($ext) || is_array($ext) && count($ext) ) {
			if( !$this->equivExt($ext) ) {
				return false;
			}
			$test = $this->last;
		}
		else if( strlen($this->ext) ) {
			return false;
		}
		else {
			$test = $this->segment[$this->length - 1];
		}

		if( $this->lower ) {
			$test = mb_strtolower( $test, BASE_ENCODING );
		}

		return $test === $segment;
	}

	public function removeExt()
	{
		if( $this->length && $this->last !== false && strlen($this->last) ) {
			$this->segment[$this->length-1] = $this->last;
			$this->last = false;
		}
	}

	public function getLast()
	{
		return $this->last;
	}

	/**
	 * @return bool
	 */
	public function isLower(): bool
	{
		return $this->lower;
	}

	protected function _request()
	{
		if( $this->mode === 'get' ) {
			$uri = '';
			if( ! empty($_GET['page']) ) {
				$uri .= $_GET['page'];
			}

			$uri = preg_replace('|/{2,}|', '', $uri);
		}

		else {
			if( ! isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) ) {
				return '';
			}

			$uri = trim($_SERVER['REQUEST_URI']);
			$pos = strpos( $uri, "?" );

			if( $pos !== false ) {
				$uri = substr( $uri, 0, $pos );
			}

			$uri = rawurldecode($uri);
			if( strpos($uri, $_SERVER['SCRIPT_NAME']) === 0 ) {
				$uri = (string) substr($uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			else if( strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0 ) {
				$uri = (string) substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}

		$uri = ltrim( $uri, "/" );
		if( $uri !== '' ) {
			$this->isDir = strrpos( $uri, "/" ) === strlen( $uri ) - 1;
			$uri = $this->_cleanRelative( $uri );
		}

		return $uri;
	}

	protected function _cleanRelative( $uri )
	{
		$uris = array();
		$tok = strtok( $uri, '/' );
		while( $tok !== false ) {
			if (( ! empty($tok) OR $tok === '0') && $tok !== '..' ) {
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}

		return implode('/', $uris);
	}
}