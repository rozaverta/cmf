<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\System\ConsoleCommands;

use EApp\Host;
use EApp\Proto\ConsoleCommand;
use EApp\Prop;
use EApp\System\ConsoleCommands\Traits\SystemInfoTrait;
use EApp\System\Fs\FileResource;

/**
 * Get system info
 *
 * @package EApp\Console
 */
class About extends ConsoleCommand
{
	use SystemInfoTrait;

	protected function exec()
	{
		$file = new FileResource('manifest', CORE_DIR . 'resources' );
		if( $file->getType() != "#/system" )
		{
			throw new \Exception("Manifest error");
		}

		// info, name, description, version, build, date

		$this->output->writeln($file->getOr("name", "Elastic-CMF"));
		$info = ["version", "build", "description", "date"];
		foreach($info as $key)
		{
			if( $file->getIs($key) )
			{
				$value = $file->get($key);
				if( $key === 'version' ) {
					$value = '<info>' . $file->get($key) . '</info>';
				}
				$this->output->writeln(ucfirst($key) . ": " . $value);
			}
		}

		// info about author

		$author = $file->getOr("authors", []);
		$at = [];

		if( is_array($author) && count($author) > 0 )
		{
			foreach($author as $one)
			{
				$at[] = $this->getAuthor($one);
			}
		}
		else if( $file->getIs('author') )
		{
			$at[] = $this->getAuthor( $file->get('author') );
		}

		$atn = count($at);
		if( $atn > 0 ) $this->output->writeln(($atn > 1 ? 'Authors: ' : 'Author: ') . implode(', ', $at));

		// check host

		if( !$this->isHost() )
		{
			$ask = $this->confirm("For more information, you need to define a host. Do you want to (y/n)? ");
			if($ask) {
				$this->showMore();
			}
		}
		else
		{
			$this->showMore();
		}
	}

	private function getAuthor($author)
	{
		if( is_object($author) )
		{
			$author = get_object_vars($author);
		}

		if( ! is_array($author) )
		{
			return (string) $author;
		}

		if( !isset($author['name']) )
		{
			return $author['email'] ?? 'unknown';
		}

		$at = trim($author['name']);
		if( isset($author['email']) )
		{
			$at .= ' <' . $author['email'] . '>';
		}

		return $at;
	}

	private function showMore()
	{
		$host = $this->getHost();
		$this->output->writeln("<info>{$host}</info>");

		$hosts = Host::getInstance();
		$application = $hosts->getApplicationDir();
		$assets = $hosts->getAssetsDir();
		$config_file = APP_DIR . 'config' . DIRECTORY_SEPARATOR . 'system.php';

		$original = $hosts->getOriginalHostName();
		if( $original !== $host ) $this->output->writeln("Original name: {$original}");
		if( $hosts->isSsl() ) $this->output->writeln("SSL: <info>yes</info>");
		if( $hosts->getPort() !== 80 ) $this->output->writeln("Port: <info>" . $hosts->getPort() . "</info>");
		$this->output->writeln("Application directory: {$application}");
		$this->output->writeln("Assets directory: {$assets}");
		$this->output->writeln("Assets path: " . $hosts->getAssetsPath());
		$this->output->writeln("Encoding: " . $hosts->getEncoding());
		$this->output->writeln("Debug mode: " . $hosts->getDebugMode());

		if( !is_dir($application) ) $this->output->writeln("<error>Warning: </error> Application directory does not exist");
		if( !is_dir($assets) ) $this->output->writeln("<error>Warning: </error> Assets directory does not exist");
		if( !file_exists($config_file) ) $this->output->writeln("<error>Warning: </error> Config file does not exist");
		else $this->showCurrentHost();
	}

	private function showCurrentHost()
	{
		$cnf = Prop::cache("system");
		// todo
	}
}