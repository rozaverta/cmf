<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.02.2016
 * Time: 20:49
 */

namespace EApp\Support;

! defined("JSON_DECODE_OPTIONS") && define("JSON_DECODE_OPTIONS", 0);
! defined("JSON_ENCODE_OPTIONS") && define("JSON_ENCODE_OPTIONS", 0);

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
	* @throws \InvalidArgumentException if the JSON cannot be decoded.
	* @link http://www.php.net/manual/en/function.json-decode.php
	*/
	public static function parse( $json, $assoc = false, $depth = 512, $options = null )
	{
		$data = json_decode(
			$json,
			$assoc,
			$depth,
			is_int($options) ? $options : JSON_DECODE_OPTIONS
		);

		$err = json_last_error();
		if(JSON_ERROR_NONE !== $err)
		{
			throw new \InvalidArgumentException(
				'json_decode error: ' . self::getError($err)
			);
		}

		return $data;
	}

	/**
	 * Wrapper for JSON encoding that throws when an error occurs.
	 *
	 * @param mixed  $value   The value being encoded
	 * @param int    $options JSON encode option bitmask
	 * @param int    $depth   Set the maximum depth. Must be greater than zero.
	 *
	 * @return string
	 * @throws \InvalidArgumentException if the JSON cannot be encoded.
	 * @link http://www.php.net/manual/en/function.json-encode.php
	 */
	public static function stringify( $value, $options = null, $depth = 512 )
	{
		if( PHP_VERSION >= 5.5 ) {
			$json = json_encode(
				$value,
				is_int($options) ? $options : JSON_ENCODE_OPTIONS,
				$depth
			);
		}
		else {
			$json = json_encode(
				$value,
				is_int($options) ? $options : JSON_ENCODE_OPTIONS
			);
		}

		$err = json_last_error();
		if (JSON_ERROR_NONE !== $err)
		{
			throw new \InvalidArgumentException(
				'json_encode error: ' . self::getError($err)
			);
		}

		return $json;
	}

	private static function getError($err)
	{
		if( PHP_VERSION >= 5.5 )
		{
			return json_last_error_msg();
		}

		switch($err)
		{
			case JSON_ERROR_DEPTH:
				$err = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$err = 'Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$err = 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$err = 'Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$err = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$err = 'Unknown error';
				break;
		}

		return $err;
	}
}