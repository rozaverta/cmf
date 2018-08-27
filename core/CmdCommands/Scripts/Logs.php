<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 13:01
 */

namespace EApp\CmdCommands\Scripts;

use EApp\App;
use EApp\Cmd\IO\Option;
use EApp\Filesystem\Iterator;
use EApp\Filesystem\SplFileCollection;

class Logs extends AbstractScript
{
	/**
	 * @var \SplFileInfo[] | SplFileCollection
	 */
	private $logs;

	public function menu()
	{
		$this->getHost();

		$this->logs = Iterator
			::createInstance(APP_DIR . "logs")
				->getFiles(1)
				->sortFileByTime()
				->filter(function (\SplFileInfo $file) {
					return $file->getExtension() === "php";
				});

		if($this->logs->count() < 1)
		{
			$this->getIO()->write("Logs not found...");
		}
		else
		{
			$this->menuPage(1);
		}
	}

	private function menuPage(int $page)
	{
		$items = [];

		$limit = 5;
		$records = count($this->logs);
		$all = $records > $limit ? ceil($records / $limit) : 1;
		if( $page > $all )
		{
			throw new \InvalidArgumentException("Invalid page number '{$page}'");
		}

		if( $page > 1 )
		{
			$prev = $page - 1;
			$items[] = new Option("prev page [{$prev}]", $prev);
		}

		$index = ($page - 1) * $limit;
		$end = $index + $limit;
		if( $end > $records )
		{
			$end = $records;
		}

		for( ; $index < $end; $index ++ )
		{
			$item = $this->logs[$index];
			$items[] = new Option($item->getBasename(".php") . " <comment>*</comment> " . $item->getSize(), $item->getPathname() );
		}

		if( $all > $page )
		{
			$next = $page + 1;
			$items[] = new Option("next page [{$next}/{$all}]", $next);
		}

		if( $page === 1 )
		{
			$items[] = new Option("remove all logs", "delete");
		}

		$items[] = new Option("exit", "exit");

		$variant = $this
			->getIO()
			->askOptions($items, "Select log");

		if( is_int($variant) )
		{
			$this->menuPage($variant);
		}
		else if( $variant === "delete" )
		{
			$this->getIO()->confirm("Are you sure (y/n)? ") && $this->deleteFile();
		}
		else if( $variant !== "exit" )
		{
			$this->menuFile($variant);
		}
	}

	private function menuFile( string $path )
	{
		$file = new \SplFileInfo($path);

		$io = $this->getIO();
		$io->write("");
		$io->write("File name: <info>" . $file->getFilename() . "</info>");
		$io->write("Size: <comment>" . $file->getSize() . "</comment> bytes");
		$io->write("Real path: " . $file->getRealPath());

		$path = $file->getRealPath();
		$base_dir = getcwd();
		if( strpos($path, $base_dir) === 0 )
		{
			$path = substr($path, strlen($base_dir) + 1);
		}

		$io->write("To view the file in Unix OS: <info>~</info> nano \"" . $path . '"');

		$variant = $this
			->getIO()
			->askOptions([
				new Option("show last 100 lines", 1),
				new Option("delete log", 2),
				new Option("exit"),
			], "Select log");

		if($variant === 1)
		{
			try {
				$this->showLinesFile($path);
			}
			catch( \RuntimeException $e )
			{
				$this->getIO()->write("<error>Wrong!</error> " . $e->getMessage() );
			}
		}
		else if($variant === 2)
		{
			$this->deleteFile($path);
		}
	}

	private function deleteFile(string $path = null)
	{
		$all = is_null($path);

		if($all)
		{
			$path = $this
				->logs
				->map(function(\SplFileInfo $file) {
					return $file->getPathname();
				})
				->toArray();
		}

		$io = $this->getIO();

		if( App::Filesystem()->delete($path) )
		{
			$io->write($all ? "All logs have been deleted" : "Log was successfully deleted");
		}
		else
		{
			$io->write("<error>Wrong!</error> Failed to delete " . ($all ? "all data" : "the log"));
		}
	}

	private function showLinesFile(string $path)
	{
		$handle = fopen($path, "r");
		if( ! $handle )
		{
			throw new \RuntimeException("Cannot open file for read");
		}

		$buffer = [];
		$len = 0;

		while(($line = fgets($handle)) !== false)
		{
			$buffer[] = $line;
			++ $len;

			if( $len > 200 )
			{
				$buffer = array_slice($buffer, $len - 100);
				$len = 100;
			}
		}

		if(! feof($handle))
		{
			throw new \RuntimeException("fgets() fail error");
		}

		@ fclose($handle);

		if( $len > 100 )
		{
			$buffer = array_slice($buffer, $len - 100);
		}

		$io = $this->getIO();

		foreach( array_reverse($buffer) as $line )
		{
			$line = trim($line);
			if( substr($line, 0, 5) === '<?php' )
			{
				continue;
			}

			if( preg_match('/^- ([0-9\-: ]+)\[([A-Z]+)\]/', $line, $m) )
			{
				$line =
					"- <comment>" . $m[1] . '</comment>'
					. ($m[2] === "ERROR" ? "[<error>ERROR</error>]" : "[<info>{$m[2]}</info>]")
					. substr($line, strlen($m[0]));
			}

			$io->write($line);
		}
	}
}