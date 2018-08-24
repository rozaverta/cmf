<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 0:33
 */

namespace EApp\Support;

class Regexp
{
	protected $pattern;

	protected $last_match = null;

	protected $last_count = 0;

	public function __construct( string $pattern )
	{
		$this->pattern = $pattern;
	}

	/**
	 * Pattern string
	 *
	 * @return string
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	/**
	 * Filled with the results of last search.
	 *
	 * @return null
	 */
	public function getLastMatch()
	{
		return $this->last_match;
	}

	/**
	 * If specified, this variable will be filled with the number of replacements done.
	 *
	 * @return int
	 */
	public function getLastCount(): int
	{
		return $this->last_count;
	}

	/**
	 * Perform a regular expression match
	 *
	 * @param string $subject The input string.
	 * @param int $flags
	 * @param int $offset Normally, the search starts from the beginning of the subject string.
	 * @return int
	 *
	 * @link http://php.net/manual/en/function.preg-match.php
	 */
	public function match(string $subject, $flags = 0, $offset = 0): int
	{
		$this->clean();
		return preg_match($this->pattern, $subject, $this->last_match, $flags, $offset);
	}

	/**
	 * Perform a global regular expression match
	 *
	 * @param string $subject The input string.
	 * @param int $flags [optional] Can be a combination of the following flags
	 * @param int $offset [optional] Normally, the search starts from the beginning of the subject string.
	 * @return int the number of full pattern matches (which might be zero), or <b>FALSE</b> if an error occurred.
	 *
	 * @link http://php.net/manual/en/function.preg-replace.php
	 */
	public function matchAll(string $subject, $flags = PREG_PATTERN_ORDER, $offset = 0)
	{
		$this->clean();
		return preg_match_all($this->pattern, $subject, $this->last_match, $flags, $offset);
	}

	/**
	 * Perform a regular expression search and replace
	 *
	 * @param mixed $replacement The string or an array with strings to replace.
	 * @param mixed $subject The string or an array with strings to search and replace.
	 * @param int $limit [optional] The maximum possible replacements for each pattern in each <i>subject</i> string. Defaults to -1 (no limit).
	 * @return mixed <b>preg_replace</b> returns an array if the <i>subject</i> parameter is an array, or a string otherwise.
	 *
	 * @link http://php.net/manual/en/function.preg-replace.php
	 */
	public function replace($replacement, $subject, int $limit = -1)
	{
		$this->clean();
		return preg_replace($this->pattern, $replacement, $subject, $limit, $this->last_count);
	}

	/**
	 * Perform a regular expression search and replace using a callback
	 *
	 * @param callable $callback
	 * @param $subject
	 * @param int $limit
	 * @param null $count
	 * @return mixed
	 *
	 * @link http://php.net/manual/en/function.preg-replace-callback.php
	 */
	public function replaceCallback(callable $callback, $subject, $limit = -1, & $count = null)
	{
		$this->clean();
		return preg_replace_callback($this->pattern, $callback, $subject, $limit, $count);
	}

	/**
	 * Perform a global regular expression match and call function for everyone
	 *
	 * @param string $subject
	 * @param \Closure $closure
	 * @param int $offset
	 */
	public function each(string $subject, \Closure $closure, $offset = 0)
	{
		if( $this->matchAll($subject, PREG_SET_ORDER, $offset) )
		{
			foreach($this->getLastMatch() as $item)
			{
				$closure($item);
			}
		}
	}

	/**
	 * @param string $subject
	 * @param \Closure $closure
	 * @param int $offset
	 * @return Collection
	 */
	public function map(string $subject, \Closure $closure, $offset = 0): Collection
	{
		$items = [];
		if( $this->matchAll($subject, PREG_SET_ORDER, $offset) )
		{
			foreach($this->getLastMatch() as $item)
			{
				$items[] = $closure($item);
			}
		}
		return new Collection($items);
	}

	/**
	 * @param string $subject
	 * @param \Closure $closure
	 * @param int $offset
	 * @return Collection
	 */
	public function filter(string $subject, \Closure $closure, $offset = 0): Collection
	{
		$items = [];
		if( $this->matchAll($subject, PREG_SET_ORDER, $offset) )
		{
			foreach($this->getLastMatch() as $item)
			{
				$match = null;
				if($closure($item, $match))
				{
					$items[] = is_null($match) ? $item[0] : $match;
				}
			}
		}
		return new Collection($items);
	}

	/**
	 * Clean last match and count results
	 *
	 * @return $this
	 */
	public function clean()
	{
		$this->last_match = null;
		$this->last_count = 0;
		return $this;
	}

	public function __toString()
	{
		return $this->getPattern();
	}
}