<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 0:05
 */

namespace EApp\View\Scheme;

use EApp\Database\Schema\SchemeDesigner;
use EApp\Support\Json;

class TemplatesSchemeDesigner extends SchemeDesigner
{
	/**
	 * Template unique identifier.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Template package identifier.
	 *
	 * @var int
	 */
	public $package_id;

	/**
	 * Template access name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Template title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Properties data.
	 *
	 * @var string
	 */
	public $properties;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->package_id = (int) $this->package_id;
		$this->properties = Json::getArrayProperties($this->properties);
	}
}