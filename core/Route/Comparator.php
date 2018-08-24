<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:03
 */

namespace EApp\Route;

use EApp\App;
use EApp\Route\Rules\RuleStatic;
use EApp\Route\Rules\RuleGet;
use EApp\Route\Rules\RuleHost;
use EApp\Route\Rules\RuleSegment;

class Comparator
{
	const MATCH_INDEX       = 1;
	const MATCH_REG_EXP     = 2;
	const MATCH_PAGE         = 3;
	const MATCH_COMPARATOR  = 4;
	const MATCH_PATH        = 5;
	const MATCH_QUERY       = 6;
	const MATCH_HOST        = 7;

	protected $url;

	protected $get_data;

	protected $parts;

	protected $part;

	protected $wait;

	protected $close;

	protected $last_match = null;

	protected $last_closable = false;

	public function __construct( Url $url, array $get_data = null )
	{
		if( ! is_array($get_data) )
		{
			$get_data = $_GET ?? [];
		}

		$this->url = $url;
		$this->get_data = $get_data;
		$this->parts = $url->count();
	}

	/**
	 * @return array | null
	 */
	public function getLastMatch()
	{
		return $this->last_match;
	}

	/**
	 * @return bool
	 */
	public function isLastClosable(): bool
	{
		return $this->last_closable;
	}

	/**
	 * @param MountPoint $mount_point
	 * @return bool
	 */
	public function match( MountPoint $mount_point ): bool
	{
		static $types = [
			"index"         => self::MATCH_INDEX,
			"match"         => self::MATCH_REG_EXP,
			"page"          => self::MATCH_PAGE,
			"comparator"    => self::MATCH_COMPARATOR,
			"path"          => self::MATCH_PATH,
			"query"         => self::MATCH_QUERY,
			"host"          => self::MATCH_HOST
		];

		$this->clean();

		$type = $mount_point->getType();

		// unknown type ?
		if( !isset($types[$type]) )
		{
			return false;
		}

		$type = $types[$type];
		$rule = $mount_point->getRule();

		switch($type)
		{
			// page is index
			case self::MATCH_INDEX : return $this->matchIndex($rule);

			// host
			case self::MATCH_HOST : return $this->matchHost($rule);

			// reg exp
			case self::MATCH_REG_EXP : return $this->matchRegexp($rule);

			// uri
			case self::MATCH_PAGE : return $this->matchUri($rule);

			// comparator
			case self::MATCH_COMPARATOR : return $rule instanceof RuleCollection ? $this->compare($rule) : false;

			// path
			case self::MATCH_PATH : return $this->matchPath($rule);

			// query
			case self::MATCH_QUERY : return is_array($rule) ? $this->matchQuery($rule) : false;
		}

		return false;
	}

	/**
	 * @param RuleCollection $collection
	 * @return bool
	 */
	public function compare( RuleCollection $collection ): bool
	{
		$this->clean();

		$count = $collection->count();
		$data_match = [];

		for( $i = 0; $i < $count; $i++ )
		{
			$item = $collection->get($i);
			$last = $i + 1 == $count;

			if( $item instanceof RuleHost )
			{
				if( ! $this->compareHost($item) ) return false;
				continue;
			}

			if( $item instanceof RuleGet )
			{
				if( ! $this->compareGet($item, $data_match) ) return false;
				continue;
			}

			if( $item instanceof RuleStatic )
			{
				$test = $this->compareStatic($item);
				if( $test )
				{
					$this->wait = -1;
					continue;
				}
			}

			else if( $item instanceof RuleSegment )
			{
				$test = $this->compareSegment($item, false, $data_match);
				if( $test )
				{
					$this->wait = ! $item->isSingle() && $item->getMin() < $item->getMax() ? $i : -1;
					continue;
				}
			}

			else
			{
				return false;
			}

			if( $this->wait < 0 )
			{
				return false;
			}

			else if( $last || $item->isRequired() )
			{
				$wait = $this->wait;
				if( $this->compareSegment($collection[$this->wait], true, $data_match) )
				{
					$i = $wait;
				}
				else
				{
					return false;
				}
			}
		}

		if($this->part !== $this->parts)
		{
			return false;
		}

		$this->last_match = $data_match;
		if( $this->close && ! $this->url->isDir() )
		{
			$this->last_closable = true;
		}

		return true;
	}

	private function compareStatic( RuleStatic $item)
	{
		// segments are over ?

		if( $this->part >= $this->parts )
		{
			return false;
		}

		// this segment is last and must be open ?

		$last = $this->part + 1 === $this->parts;
		if( $last && $this->url->isDir() && $item->isOpen() )
		{
			return false;
		}

		if( $item->match($this->url->getSegment($this->part)) )
		{
			++ $this->part;
			if($last)
			{
				$this->close = ! $item->isOpen();
			}
			return true;
		}

		return false;
	}

	private function compareHost(RuleHost $item)
	{
		$test =
			$this->url->getProtocol() . "://" .
			$this->url->getHost() . ":" .
			$this->url->getPort() . "/";

		return $item->match($test);
	}

	private function compareSegment(RuleSegment $item, bool $force, & $match)
	{
		// segments are over ?

		if( $this->part >= $this->parts )
		{
			return false;
		}

		// this segment is last and must be open ?

		$last = $this->part + 1 === $this->parts;
		if( $last && $this->url->isDir() && $item->isOpen() )
		{
			return false;
		}

		$name = $item->getName();

		// single segment ?

		if( $item->isSingle() )
		{
			$test = $this->url->getSegment($this->part);

			if( $item->match($test, $m) )
			{
				++ $this->part;
				$match[$name] = is_null($m) ? $test : $m;
				if($last)
				{
					$this->close = ! $item->isOpen();
				}
				return true;
			}

			return false;
		}

		// create empty data

		if( ! isset($match[$name]) )
		{
			$match[$name] = [];
		}

		$cur = count($match[$name]);
		$min = $item->getMin();

		if($cur < $min || $force)
		{
			// match segment

			$part = $this->part;
			$segment = $this->url->getSegment($part);
			if( ! $item->match($segment, $m) )
			{
				return false;
			}

			// add segment to global data matches

			$match[$name][] = is_null($m) ? $segment : $m;
			++ $cur;
			++ $this->part;

			// last segment, stop waiting

			if( $force && $cur === $item->getMax() )
			{
				$this->wait = -1;
			}

			// check the minimum length and perform a search if necessary

			if( $cur < $min && ! $this->compareSegment($item, false, $match) )
			{
				$this->part = $part;
				unset($match[$name]);
				return false;
			}
		}

		if($last)
		{
			$this->close = ! $item->isOpen();
		}

		return true;
	}

	private function compareGet(RuleGet $item, & $match)
	{
		$name = $item->getQueryName();

		if( ! isset($this->get_data[$name]) )
		{
			if( $item->isRequired() )
			{
				return false;
			}
		}
		else
		{
			$test = $this->get_data[$name];
			if( is_array($test) )
			{
				$test = count($test) ? (string) current($test) : "";
			}

			if( $item->match($test, $m) )
			{
				$match[$item->getName()] = is_null($m) ? $test : $m;
			}
			else if( ! $item->isRequired() )
			{
				return false;
			}
		}

		return true;
	}

	private function clean()
	{
		$this->part = 0;
		$this->wait = -1;
		$this->close = true;
		$this->last_match = null;
		$this->last_closable = false;
	}

	private function matchIndex( string $page_name ): bool
	{
		$url = $this->url;

		if( $url->count() === 1 && $url->getDirLength() < 1 )
		{
			if( ! strlen($page_name) )
			{
				$page_name = $url
					->getConfig()
					->get("index_page");
			}

			if($page_name !== $url->getSegment(0))
			{
				return false;
			}
		}
		else if( $url->count() === 0 )
		{
			$page_name = "";
		}
		else
		{
			return false;
		}

		$this->last_match = [
			"page_name" => $page_name
		];

		return true;
	}

	private function matchHost( string $rule ): bool
	{
		$url = $this->url;
		$host = parse_url($rule);

		$valid = $host
			&& isset($host["host"])
			&& $host["host"] === $url->getHost()
			&& ( !isset($host["scheme"]) || $host["scheme"] === $url->getProtocol() )
			&& ( !isset($host["port"]) || intval($host["port"]) === $url->getPort() );

		if( !$valid )
		{
			return false;
		}

		$port = $url->getPort();
		$this->last_match = [
			"scheme" => $url->getProtocol() . "://" . $url->getHost() . ($port !== 80 && $port > 0 ? (":" . $port) : "") . "/"
		];

		return true;
	}

	private function matchRegexp( string $regexp ): bool
	{
		if( !strlen($regexp) )
		{
			return false;
		}
		else
		{
			return preg_match( $regexp, $this->url->getPath(), $this->last_match );
		}
	}

	private function matchUri( string $rule ): bool
	{
		if( ! strlen($rule) || $rule[0] !== "/" )
		{
			$rule = "/" . $rule;
		}

		$path = $this->url->getPath();
		if( $path !== $rule )
		{
			$path .= "/";
			if( $path === $rule )
			{
				$this->last_closable = true;
			}
			else
			{
				return false;
			}
		}

		$this->last_match = [
			"path" => $path
		];

		return true;
	}

	private function matchPath( string $rule ): bool
	{
		$rule = trim($rule, "/");
		if( !strlen($rule) )
		{
			return false;
		}

		$rule = explode("/", $rule);
		$length = count($rule);
		if( $length > $this->url->count() )
		{
			return false;
		}

		$match = [];
		for( $i = 0; $i < $length; $i++ )
		{
			if( $this->url->getSegment($i) !== $rule[$i] )
			{
				return false;
			}
			else
			{
				$match[] = $rule[$i];
			}
		}

		if( $this->url->getDirLength() < $length )
		{
			$this->last_closable = true;
		}

		$this->last_match = compact('length', 'match');
		return true;
	}

	private function matchQuery( array $rule ): bool
	{
		$inp = App::Request();
		$match = [];

		foreach( $rule as $key => $value )
		{
			$get = $inp->get($key);
			if( $get === null || strlen($value) && $value !== $get )
			{
				return false;
			}
			else
			{
				$match[$key] = $get;
			}
		}

		$this->last_match = $match;
		return true;
	}
}