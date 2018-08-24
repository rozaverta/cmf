<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.02.2016
 * Time: 20:49
 */

namespace EApp\Support;

use Closure;
use EApp\Prop;
use EApp\Interfaces\Arrayable;
use EApp\Interfaces\Jsonable;
use InvalidArgumentException;

/**
 * Class Json
 * @package EApp\Support
 * @method static Json getInstance()
 */
class Json
{
	/**
	* Wrapper for json_decode that throws when an error occurs.
	*
	* @param string $json    JSON data to parse
	* @param bool $assoc     When true, returned objects will be converted
	*                        into associative arrays.
	* @param int    $depth   User specified recursion depth.
	* @param int    $options Bitmask of JSON decode options.
	*
	* @return mixed
	* @throws InvalidArgumentException if the JSON cannot be decoded.
	* @link http://www.php.net/manual/en/function.json-decode.php
	*/
	public static function parse( $json, $assoc = false, $depth = 512, $options = null )
	{
		$data = json_decode(
			$json,
			$assoc,
			$depth,
			is_int($options) ? $options : self::jsonDecodeOptions()
		);

		$err = json_last_error();
		if(JSON_ERROR_NONE !== $err)
		{
			throw new InvalidArgumentException(
				'json_decode error: ' . json_last_error_msg()
			);
		}

		return $data;
	}

	/**
	 * @param mixed $data
	 * @param bool $throw
	 * @return array
	 */
	public static function getArrayProperties( $data, $throw = false ): array
	{
		if( $data instanceof Closure )
		{
			$data = $data();
		}

		if( $data instanceof Jsonable )
		{
			$data = $data->toJson();
		}

		if( is_object($data) )
		{
			return $data instanceof Arrayable ? $data->toArray() : get_object_vars($data);
		}

		if( is_string($data) )
		{
			if( $throw ) {
				$data = self::parse($data, true);
			}
			else {
				try {
					$data = self::parse($data, true);
				}
				catch( InvalidArgumentException $e ) {
					$data = [];
				}
			}
		}

		if( ! is_array($data) )
		{
			if( $throw )
			{
				throw new InvalidArgumentException('json_decode error: result must be array');
			}
			$data = [];
		}

		return $data;
	}

	/**
	 * Wrapper for JSON encoding that throws when an error occurs.
	 *
	 * @param mixed  $value   The value being encoded
	 * @param int    $options JSON encode option bitmask
	 * @param int    $depth   SetTrait the maximum depth. Must be greater than zero.
	 *
	 * @return string
	 * @throws InvalidArgumentException if the JSON cannot be encoded.
	 * @link http://www.php.net/manual/en/function.json-encode.php
	 */
	public static function stringify( $value, $options = null, $depth = 512 )
	{
		$json = json_encode(
			$value,
			is_int($options) ? $options : self::jsonEncodeOptions(),
			$depth
		);

		$err = json_last_error();
		if(JSON_ERROR_NONE !== $err)
		{
			throw new InvalidArgumentException(
				'json_encode error: ' . json_last_error_msg()
			);
		}

		return $json;
	}

	public static function jsonDecodeOptions()
	{
		if( ! defined("JSON_DECODE_OPTIONS") )
		{
			$prop = Prop::cache('json');
			$decode_options = 0;

			if( $prop->isInt('decode_options') )
			{
				$decode_options = $prop->get('decode_options');
			}
			else if( $prop->equiv('bigint_as_string', true) )
			{
				$decode_options = JSON_BIGINT_AS_STRING;
			}

			define("JSON_DECODE_OPTIONS", $decode_options);
		}

		return JSON_DECODE_OPTIONS;
	}

	public static function jsonEncodeOptions()
	{
		if( ! defined("JSON_ENCODE_OPTIONS") )
		{
			$prop = Prop::cache('json');
			$encode_options = 0;

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

			define("JSON_ENCODE_OPTIONS", $encode_options);
		}

		return JSON_ENCODE_OPTIONS;
	}
}