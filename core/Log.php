<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:45
 */

namespace EApp;

use EApp\Interfaces\Arrayable;

class Log implements Arrayable, \JsonSerializable
{
	public $text;

	public $level = "ERROR";

	public $code = 0;

	public $replace = false;

	private $time;

	private $line = false;

	private $translate = false;

	public function __construct( $text, $level = null, $code = 0 )
	{
		$this->time = time();

		if( $text instanceof \Throwable )
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
				function($m) {
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

	public function setLine(bool $line = true)
	{
		$this->line = $line;
		return $this;
	}

	/**
	 * @param bool $translate
	 * @return $this
	 */
	public function setTranslate( bool $translate = true )
	{
		$this->translate = $translate;
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
				return $this->setTranslate(false)->message();
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
			$text = "- " . date( "Y-m-d H:i:s", $this->time ) . ' [' . $this->level . '] ';
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

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}