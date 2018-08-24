<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 15:23
 */

namespace EApp\Http\Collections;

use EApp\Support\Collection;

class ServerCollection extends Collection
{
	protected $http_header_prefix = 'HTTP_';

	protected $http_nonprefixed_headers =
		[
			'CONTENT_LENGTH',
			'CONTENT_TYPE',
			'CONTENT_MD5',
		];

	/**
	 * GetTrait our headers from our server data collection
	 *
	 * PHP is weird... it puts all of the HTTP request
	 * headers in the $_SERVER array. This handles that
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		// Define a headers array
		$headers = [];

		foreach($this->items as $key => $value)
		{
			// Does our server attribute have our header prefix ?
			if(strpos($key, $this->http_header_prefix) === 0)
			{
				$key = substr($key, strlen($this->http_header_prefix));
			}
			else if(! in_array($key, $this->http_nonprefixed_headers))
			{
				continue;
			}

			$key = HeaderCollection::normalizeKey($key);

			// Add our server attribute to our header array
			$headers[$key] = $value;
		}

		return $headers;
	}
}