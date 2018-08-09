<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 20:06
 */

namespace EApp\System\Script;

class Install extends AbstractScript
{
	public function run()
	{
		$io = $this->getIO();
		$io->write("<info>BASE_DIR:</info> " . $this->getBaseDir());

		if( $io->askConfirmation("Add index and hosts files (y/n)? ") )
		{
			$this->create();
		}
	}

	private function create()
	{
		$io = $this->getIO();
		if( !$io->askConfirmation("Add index and hosts files (y/n)? ") )
		{
			return;
		}

		$index_name = $this->askFileName('index');
		$hosts_name = $this->askFileName('hosts');
		$htc_file = false;
		$default_host = false;

		// create default hosts ?

		if($io->askConfirmation("Create default host (y/n)? "))
		{
			$default_host = $this->askHost();
		}

		// check software, create .htaccess file

		$software = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
		$apache = strpos($software, 'apache') !== false;
		$nginx = ! $apache && strpos($software, 'nginx') !== false;

		if( $apache )
		{
			$htc_file = !
				file_exists($this->getBaseDir() . ".htaccess") &&
				$io->askConfirmation(".htaccess file not found. Create (y/n)? ");
		}

		// create content for index file

		$this->createFile("index", $index_name, '<?php defined("ELS_CMS") || exit;
define("ELS_CMS", true);
define("BASE_DIR", __DIR__ . DIRECTORY_SEPARATOR);
include BASE_DIR . "vendor/autoload.php";
\EApp\App::getInstance()->run();
');

		// create content for hosts file (empty)

		$this->createFile("hosts", $hosts_name, '<?php defined("ELS_CMS") || exit;
$hosts = [' . ($default_host ? (var_export($default_host) . ' => []') : '') . '];
');

		// create content for htaccess file

		if( $htc_file ) $this->createFile("htaccess", ".htaccess", '# auto generation rewrite rule
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php?q=$1 [L,QSA]
');
		else {
			$io->write("<info>WARNING!</info> You must create or add rewrite rule for the index file '{$index_name}'");
			if( $apache )
			{
				$io->write("# Example for Apache server");
				$rules = [
					'RewriteEngine on',
					'RewriteCond %{REQUEST_FILENAME} !-f',
					'RewriteCond %{REQUEST_FILENAME} !-d',
					'RewriteRule ^(.*)$ /' . $index_name . '?q=$1 [L,QSA]'
				];
			}
			else if( $nginx )
			{
				$io->write("; Example for Nginx server");
				$rules = ['location / {', "\ttry_files \$uri \$uri/ /' . $index_name . '?\$args;", '}'];
			}
			else
			{
				return;
			}

			foreach($rules as $rule)
			{
				$io->write($rule);
			}
		}
	}

	private function createFile($type, $file_name, $text)
	{
		$file = $this->getBaseDir() . $file_name;

		if( $fo = @ fopen( $file, "wa+" ) )
		{
			if( @ flock( $fo, LOCK_EX ) )
			{
				flock(  $fo, LOCK_UN );
				fwrite( $fo, $text );
				fflush( $fo );
				flock(  $fo, LOCK_UN );
			}

			@ fclose( $fo );

			$ready = @ file_get_contents($file);

			if( $ready && md5($ready) === md5($text) )
			{
				$this
					->getIO()
					->write("<error>Info: </error> the {$type} file was successfully created");
			}
			else if( file_exists($file) )
			{
				$this
					->getIO()
					->write("<error>Error: </error> during the creation of the {$type} file, errors occurred");
			}
			else
			{
				$this
					->getIO()
					->write("<error>Error: </error> cannot create the {$type} file {$file_name}");
			}
		}
		else
		{
			$this
				->getIO()
				->write("<error>Error: </error> cannot create the {$type} file {$file_name}");
		}
	}

	private function askFileName($name)
	{
		$index_name = $this
			->getIO()
			->ask("Enter {$name} file name [{$name}.php]: ", $name . ".php");

		$index_name = trim($index_name);

		if( ! strlen($index_name) )
		{
			$index_name = $name .".php";
		}

		if( strlen($index_name) > 25 || ! preg_match('/^[a-z][a-z0-9\-]+[a-z0-9]$/', $index_name) )
		{
			$this
				->getIO()
				->write("<error>Error: </error> invalid {$name} name '{$index_name}', use [a-z0-9\-] symbol, maximum length of 25 characters");

			return $this->askFileName($name);
		}

		if( strpos($index_name, ".") === false )
		{
			$index_name .= ".php";
		}

		// check exists

		$file = $this->getBaseDir() . $index_name;
		if( file_exists($file) )
		{
			$overwrite = $this
				->getIO()
				->askConfirmation("The specified file already exists. Overwrite it (y/n)? ");

			if( !$overwrite )
			{
				return $this->askFileName($name);
			}
		}

		return $index_name;
	}

	private function askHost()
	{
		$host = $this
			->getIO()
			->ask("Enter default host/domain name [localhost]: ", "localhost");

		$host = trim($host);
		if( !strlen($host) )
		{
			return "localhost";
		}

		$host = filter_var($host, FILTER_VALIDATE_DOMAIN);
		if( !$host )
		{
			$this
				->getIO()
				->write("<error>Error: </error> invalid host name '{$host}'");

			return $this->askHost();
		}

		return $host;
	}
}