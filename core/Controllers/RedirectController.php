<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.09.2017
 * Time: 2:22
 */

namespace EApp\Controllers;

use EApp\App;
use EApp\Prop;
use EApp\Controllers\Interfaces\ControllerContentOutput;
use EApp\Module\Module;

final class RedirectController extends Controller implements ControllerContentOutput
{
	/** @noinspection PhpMissingParentConstructorInspection
	 *
	 * RedirectController constructor.
	 *
	 * @param Module $module
	 * @param array $data
	 */
	public function __construct( Module $module, array $data = [] )
	{
		$this->module = $module;
		$this->properties = new Prop($data);
	}

	public function ready()
	{
		App::Response()->redirect(
				$this->properties->getOr("location", "/"),
				$this->properties->getOr("permanent", false),
				$this->properties->getOr("refresh", false)
			);
		return true;
	}

	/**
	 * Render content is raw output.
	 *
	 * @return boolean
	 */
	public function isRaw()
	{
		return true;
	}

	/**
	 * Render content.
	 *
	 * @return void
	 */
	public function output()
	{
		$response = App::Response();
		if( !$response->isSent() )
		{
			$response->send();
		}
	}
}