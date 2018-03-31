<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:45
 */

namespace EApp;

use EApp\Support\Interfaces\Arrayable;

class Log implements Arrayable
{
	public $text;

	public $level = "ERROR";

	public $code = 0;

	public $replace = false;

	private $time;
	private $line = false;
	private $translate = true;

	public function __construct( $text, $level = null, $code = 0 )
	{
		$this->time = time();

		if( PHP_VERSION >= 7 && $text instanceof \Throwable || $text instanceof \Exception )
		{
			$this->text = $text->getMessage();
			$this->messageBounceBack();
			if( !$code )
			{
				$code = $text->getCode();
			}
		}
		else if( $text instanceof Text )
		{
			$this->text = $text->text;
			$this->replace = $text->replace;
		}
		else
		{
			$this->text = (string) $text;
		}

		if( $level )
		{
			$this->level = strtoupper( $level );
		}

		if( $code > 0 )
		{
			$this->code = (int) $code;
		}
	}

	public function messageBounceBack()
	{
		if( ! $this->replace )
		{
			$this->replace = [];
			$this->text = preg_replace_callback(
				'/\'(.*?)\'/',
				static function($m) {
					$this->replace[] = trim($m[1]);
					return "'%s'";
				},
				$this->text,
				-1,
				$count
			);

			if( !$count )
			{
				$this->replace = false;
			}
		}

		return $this;
	}

	public function line()
	{
		$this->line = true;
		return $this;
	}

	public function translateOff()
	{
		$this->translate = false;
		return $this;
	}

	public function message()
	{
		static $lang;
		static $init  = false;
		static $fatal = false;

		if( $this->translate )
		{
			if( !$init )
			{
				$init = true;
				try
				{
					$lang = App::Lang();
				}
				catch(\Exception $e)
				{
					$fatal = true;
				}
			}

			if( $fatal )
			{
				return $this->translateOff()->message();
			}

			if( $this->replace !== false )
			{
				return $lang->replace( $this->text, $this->replace );
			}
			else
			{
				return $lang->line( $this->text );
			}
		}

		if( $this->replace !== false )
		{
			return vsprintf( $this->text, (array) $this->replace );
		}
		else
		{
			return $this->text;
		}
	}

	public function __toString()
	{
		$text = "";
		if( $this->line )
		{
			$text = "- " . date( "Y-m-d H:i", $this->time ) . ' [' . $this->level . '] ';
		}

		$text .= $this->message();
		return $text;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			"level"   => $this->level,
			"message" => $this->message(),
			"time"    => $this->time,
			"code"    => $this->code
		];
	}
}