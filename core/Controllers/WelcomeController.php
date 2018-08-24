<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.08.2018
 * Time: 18:19
 */

namespace EApp\Controllers;

use EApp\App;
use EApp\Component\QueryModules;
use EApp\Component\Scheme\ModuleSchemeDesigner;
use EApp\Prop;
use EApp\Exceptions\NotFoundException;
use EApp\Filesystem\Resource;

class WelcomeController extends Controller
{
	protected $page;

	protected $menu = [
		[
			"id"    => 1,
			"name"  => "index",
			"title" => "Welcome"
		],
		[
			"id"    => 2,
			"name"  => "system",
			"title" => "System"
		],
		[
			"id"    => 3,
			"name"  => "license",
			"title" => "License"
		]
	];

	public function ready()
	{
		$url = App::Url();

		$this->id  = 404;
		$page_name = $url->count() === 0 ? "index" : ($url->count() === 1 && $url->getDirLength() === 0 ? $url->getSegment(0) : "");

		foreach($this->menu as & $page)
		{
			$page["link"] = $url->makeURL($page["name"], [], true, true);
			$page["active"] = $page["name"] === $page_name;
			if($page["active"])
			{
				$this->id = $page["id"];
				$this->page = $page;
			}
		}

		if( $this->id === 404 )
		{
			$this->page = [
				"id"        => $this->id,
				"name"      => "404",
				"link"      => $url->getUrl(),
				"title"     => "Page not found",
				"active"    => true
			];

			$this->menu[] = $this->page;
		}

		return true;
	}

	public function complete()
	{
		$this->page_data["page_title"] = $this->page["title"];
		$this->page_data["menu"] = $this->menu;
		$this->{"loadContent" . ucfirst($this->page["name"])}();
	}

	protected function loadContentIndex()
	{
		$name = Prop
			::cache("system")
			->getOr("name", "Elastic-CMF");

		$this->page_data["content"] = "<h3>{$name}</h3>

<p>Elastic CMF (Content Management Framework) 
is a system that facilitates the use of reusable components or customized 
software for managing Web content. It shares aspects of a Web application 
framework and a content management system (CMS).</p>

<p>This is the default page. You did not specify mount points.</p>";
	}

	protected function loadContentSystem()
	{
		$system = Prop::cache("system");
		$db = Prop::cache("db")->getOr("default", []);

		$body  = "<h3>Base info</h3>";
		$body .= "<p><strong>Name:</strong> " . $system->getOr("name", "Elastic-CMF");
		$body .= " v&nbsp;" . $system->get("version");
		if( $system->getIs("build") ) $body .= " build \$" . $system->get("build");
		$body .= "<br>";
		$body .= "<strong>Domain:</strong> " . APP_HOST . "<br>";
		$body .= "<strong>Web-site:</strong> " . $system->getOr("site_name", APP_HOST) . "</p>";
		$body .= "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";

		$body .= "<h3>Database</h3>";
		$body .= "<p><strong>Driver:</strong> " . ($db["driver"] ?? "mysql") . "<br>";
		$body .= "<strong>Charset:</strong> " . ($db["charset"] ?? "-") . "<br>";
		if( ! empty($db["collation"]) ) $body .= "<strong>Collation:</strong> " . $db["collation"] . "<br>";
		if( ! empty($db["prefix"]) ) $body .= "<strong>Prefix:</strong> " . $db["prefix"] . "</p>";

		$lst = new QueryModules();
		$clt = $lst->get();
		if( $clt->count() > 0 )
		{
			$body .= "<h3>Modules</h3>";
			$body .= "<p>";

			/** @var ModuleSchemeDesigner $module */
			foreach( $clt as $module )
			{
				$body .= '<p><strong>' . $module->name . ':</strong> ' . $module->title;
				$body .= ' v&nbsp;' . $module->version;
				if( !$module->install )
				{
					$body .= ' <span class="warn">[not install]</span>';
				}

				try {
					$manifest = new Resource('manifest', $module->path . "resources");
					$manifest->ready();

					if( $manifest->getIs("description") )
					{
						$body .= '<br>' . $manifest->get("description");
					}
				}
				catch(NotFoundException $e) {}

				$body .= '</p>';
			}
		}
		else
		{
			$body .= '<p class="warn">Modules not added.</p>';
		}

		$this->page_data["content"] = $body;
	}

	protected function loadContentLicense()
	{
		$this->page_data["content"] = '<h3>MIT License</h3>

<p>Copyright (c) 2018 RozaVerta</p>

<p>Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:</p>

<p>The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.</p>

<p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.</p>';
	}

	protected function loadContent404()
	{
		$this->page_data["content"] = '<h3>404</h3><p>' . App::Url()->getUrl() . '</p><p>The page are you looking for cannot be found.</p>';
	}
}