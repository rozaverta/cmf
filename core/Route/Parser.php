<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 14:54
 */

namespace EApp\Route;

use EApp\App;
use EApp\Route\Interfaces\RuleInterface;
use EApp\Route\Rules\RuleStatic;
use EApp\Route\Rules\RuleGet;
use EApp\Route\Rules\RuleHost;
use EApp\Route\Rules\RuleSegment;

class Parser
{
	/**
	 * Create URL string from RuleCollection instance
	 *
	 * @param array $data
	 * @param RuleCollection $collection
	 * @param Url|null $url
	 * @return string
	 */
	public static function makeUrl( array $data, RuleCollection $collection, Url $url = null ): string
	{
		$path = [];
		$get = [];
		$close = true;
		$protocol = "";
		$host = "";
		$port = 80;

		/** @var RuleInterface $item */
		foreach( $collection as $item )
		{
			if( $item instanceof RuleHost )
			{
				$protocol = $item->getProtocol();
				$host = $item->getHost();
				$port = $item->getPort();
			}

			else if( $item instanceof RuleStatic )
			{
				$path[] = $item->getSegment();
				$close = ! $item->isOpen();
			}

			else if( $item instanceof RuleSegment )
			{
				$name = $item->getName();
				if( ! isset($data[$name]) || is_array($data[$name]) && ! count($data[$name]) )
				{
					if( $item->isRequired() )
					{
						throw new \InvalidArgumentException("Insufficient data");
					}
				}

				else if( $item->isSingle() )
				{
					$path[] = $item->getPrefix() . ( is_array($data[$name]) ? current($data[$name]) : $data[$name] ) . $item->getSuffix();
				}

				else
				{
					$values = is_array($data[$name]) ? $data[$name] : [$data[$name]];
					foreach($values as $segment)
					{
						$path[] = $item->getPrefix() . $segment . $item->getSuffix();
					}
				}
			}
			else if( $item instanceof RuleGet )
			{
				$name = $item->getName();
				if( !isset($data[$name]) || ! $item->match($data[$name]))
				{
					if( $item->isRequired() )
					{
						throw new \InvalidArgumentException("Insufficient data");
					}
				}
				else
				{
					$get[$item->getQueryName()] = $data[$name];
				}
			}
		}

		// create path

		if( count($path) )
		{
			$path = "/" . implode("/", $path);
			if( $close )
			{
				$path .= "/";
			}
		}
		else
		{
			$path = "/";
		}

		if( count($get) )
		{
			$path .= "?" . http_build_query($get);
		}

		// create host

		if( ! strlen($host) )
		{
			if( ! is_null($url) )
			{
				$protocol = $url->getHost();
			}
			else if( defined("APP_HOST") )
			{
				$protocol = APP_HOST;
			}
			else if( App::getInstance()->loadIs("Url") )
			{
				$protocol = App::Url()->getHost();
			}
			else
			{
				return $path;
			}
		}

		// create protocol

		if( ! strlen($protocol) )
		{
			if( ! is_null($url) )
			{
				$protocol = $url->getProtocol();
			}
			else if( defined("BASE_PROTOCOL") )
			{
				$protocol = BASE_PROTOCOL;
			}
			else
			{
				$protocol = "http";
			}
		}

		// add port

		if( $port !== 80 )
		{
			$host .= ":" . $port;
		}

		return $protocol . "://" . $host . $path;
	}

	/**
	 * Parse RULE string
	 *
	 * @param string $parse
	 * @param array $properties
	 * @return RuleCollection
	 */
	public static function parse( string $parse, array $properties = [] ): RuleCollection
	{
		$clt = new RuleCollection();

		if( preg_match('/^(https?):\/\/(.+?)(\/$)$/', $parse, $m) )
		{
			$parse = $m[3];
			$clt[] = self::getRuleHost($m[2], $m[1]);
		}
		else if( preg_match('/^\/\/(.+?)(\/$)$/', $parse, $m) )
		{
			$parse = $m[2];
			$clt[] = self::getRuleHost($m[1]);
		}

		$parse  = ltrim($parse, "/");
		$end_of = strrpos($parse, "/");
		$close  = $end_of !== false && $end_of === strlen($parse) - 1;
		$get    = '';
		$names  = [];

		if($close)
		{
			$parse = rtrim($parse, "/");
		}

		$parse = explode("/", $parse);
		$cnt = count($parse);

		for($i = 0; $i < $cnt; $i++)
		{
			$item = trim($parse[$i]);
			$last = $i + 1 === $cnt;

			if( $item[0] === "{" )
			{
				$end_of = strpos($item, "}");
				if( $end_of === false )
				{
					throw new \InvalidArgumentException("Parser error, invalid segment value '{$item}'");
				}

				$rule = self::getRuleSegment(substr($item, 1, $end_of - 2), $properties);
				if( in_array($rule->getName(), $names, true) )
				{
					throw new \InvalidArgumentException("Duplicate data name '" . $rule->getName() . "'");
				}
				else
				{
					$names[] = $rule->getName();
				}

				$clt[] = $rule;
				$end = $end_of + 1;
				$len = strlen($item);

				if( $end > $len )
				{
					// start GET queries
					if( $last && $item[$end] === "?" && $end + 1 < $len )
					{
						$get = substr($item, $end);
						$close = false;
					}
					else
					{
						throw new \InvalidArgumentException("Parser error, invalid segment value '{$item}'");
					}
				}
			}

			// start GET queries
			else if( $item[0] === "?" )
			{
				if( $last && strlen($item) > 1 )
				{
					if( $i > 0 )
					{
						$close = true;
					}
					$get = substr($item, 1);
				}
				else
				{
					throw new \InvalidArgumentException("Parser error, invalid segment value '{$item}'");
				}

				break;
			}

			// static ...
			else
			{
				$clt[] = new RuleStatic($item);
			}
		}

		if( ! $close )
		{
			$last = $clt->last();
			if( $last instanceof RuleStatic || $last instanceof RuleSegment )
			{
				$last->setOpen();
			}
		}

		if( strlen($get) )
		{
			if( strpos($get, '&amp;') !== false )
			{
				$all = explode("&amp;", $get);
			}
			else
			{
				$all = explode("&", $get);
			}

			$queries = [];

			foreach( $all as $item )
			{
				$item = trim($item);
				if( !strlen($item) )
				{
					throw new \InvalidArgumentException("Parser error, query value is empty");
				}

				if( strpos($item, '{') === false )
				{
					$m = [
						1 => $item,
						2 => $item
					];
				}
				else if( ! preg_match('/^(.+?)=\{(.+)?\}$/', $item, $m) )
				{
					throw new \InvalidArgumentException("Parser error, invalid query value '{$item}'");
				}

				$rule = self::getRuleGet($m[1], $m[2], $properties);
				if( in_array($rule->getName(), $names, true) )
				{
					throw new \InvalidArgumentException("Duplicate data name '" . $rule->getName() . "'");
				}
				else
				{
					$names[] = $rule->getName();
				}

				if( in_array($rule->getQueryName(), $queries, true) )
				{
					throw new \InvalidArgumentException("Duplicate query name '" . $rule->getName() . "'");
				}
				else
				{
					$queries[] = $rule->getName();
				}

				$clt[] = $rule;
			}
		}

		return $clt;
	}

	protected static function getRuleHost(string $host, string $protocol = ""): RuleHost
	{
		if( preg_match('/^(.+?):(\d+)$/', $host, $m ) )
		{
			return new RuleHost($m[1], $protocol, (int) $m[2]);
		}
		else
		{
			return new RuleHost($host, $protocol);
		}
	}

	protected static function getRuleSegment(string $segment, array & $properties )
	{
		$split = explode(":", $segment);
		$split = array_map('trim', $split);

		// name
		if( !strlen($split[0]) )
		{
			throw new \InvalidArgumentException("Parser error, invalid segment value '{$segment}'");
		}

		$name = $split[0];

		return new RuleSegment(
			$name,
			$split[1] ?? "",
			$split[2] ?? "",
			$split[3] ?? "",
			$split[4] ?? "",
			$properties[$name] ?? null
		);
	}

	protected static function getRuleGet(string $query_name, string $segment, array & $properties )
	{
		$split = explode(":", $segment);
		$split = array_map('trim', $split);

		// name
		if( !strlen($split[0]) )
		{
			throw new \InvalidArgumentException("Parser error, invalid query value '{$segment}'");
		}

		$name = $split[0];

		return new RuleGet(
			$query_name,
			$name,
			$split[1] ?? "",
			strtolower($split[2] ?? "") === "required",
			$properties[$name] ?? null
		);
	}
}