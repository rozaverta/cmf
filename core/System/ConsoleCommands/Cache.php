<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\System\ConsoleCommands;

use EApp\Proto\ConsoleCommand;
use EApp\System\ConsoleCommands\Traits\SystemInfoTrait;
use EApp\System\Fs\CacheCalculate;
use EApp\System\Fs\CacheFs;
use EApp\System\Fs\CacheInfo;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Get system cache info and clean cache data
 *
 * @package EApp\System\ConsoleCommands
 */
class Cache extends ConsoleCommand
{
	protected function init()
	{
		$this->addArgument("point", InputArgument::OPTIONAL, "File or directory", "");
		$this->addOption("clean", "c", InputOption::VALUE_NONE, "Clean cache");
		$this->addOption("list", "l", InputOption::VALUE_NONE, "List result files");
		$this->addOption("info", "i", InputOption::VALUE_NONE, "Get cache info (use this options as default)");
	}

	protected function exec()
	{
		$this->getHost();

		if( !$this->hasInstall() )
		{
			throw new \InvalidArgumentException("System is not installed");
		}

		$point = trim($this->input->getArgument("point"), "/");
		$clean = $this->input->getOption("clean");
		$list  = $this->input->getOption("list");
		$calculate = ! $clean && ( ! $list || $this->input->getOption("info") );

		if( $clean && $list )
		{
			throw new \InvalidArgumentException("You cannot use --list and --clean option options together");
		}

		if($point)
		{
			$info = new CacheInfo($point);
			if($info->isFile())
			{
				$this->file($info, $calculate, $list);
				return;
			}
			$fs = $info->fs();
		}
		else
		{
			$fs = new CacheFs();
		}

		if( $calculate )
		{
			$this->calculate($fs->calculate(), $list, $fs->getRelativePath());
		}

		else if( $list )
		{
			$this->ls($fs->ls(), $fs->getRelativePath());
		}

		else
		{
			$this->clean( $fs->clean(true), $fs->getLogs(true), $fs->getRelativePath() );
		}
	}

	protected function file(CacheInfo $info, bool $calculate, bool $list)
	{
		if( $calculate )
		{
			$calc = new CacheCalculate();
			$calc->add($info);
			$this->calculate($calc, $list, $info->getRelativePath());
		}
		else if( $list )
		{
			$path = $info->getRelativePath();
			$end = strrpos($path, DIRECTORY_SEPARATOR);
			if( $end !== false )
			{
				$path = substr($path, 0, $end);
			}
			else
			{
				$path = "";
			}
			$this->ls( [$info], $path );
		}
		else
		{
			$this->clean($info->clean() ? 1 : 0, $info->getLogs(true), $info->getRelativePath() );
		}
	}

	protected function calculate(CacheCalculate $calc, bool $list, string $path)
	{
		$this->output->writeln('<info>Cache info' . $this->getPath($path, " (%s)") . '</info>');
		$this->output->writeln('File(s): <info>' . $calc->count() . '</info>');
		$this->output->writeln('Size: <info>' . $this->getSizeUnits($calc->getSize()) . '</info>');
		if($list)
		{
			$this->ls($calc, "");
		}
	}

	protected function ls($list, string $path)
	{
		if(strlen($path))
		{
			$this->output->writeln('<info>Cache path:</info> '. $this->getPath($path));
		}

		$pref = APP_DIR . "cache";
		$len = strlen($pref);

		$n = 1;
		$table = new Table( $this->output );
		$table->setHeaders( [ '#', 'Path', 'Size or type' ] );

		/** @var \SplFileInfo $file */
		foreach($list as $file)
		{
			$path = $file->getPath();
			if(strpos($path, $pref) === 0)
			{
				$path = substr($path, $len);
			}

			$path .= DIRECTORY_SEPARATOR . $file->getFilename();

			if( $file->isLink() ) $size = '<info>[link]</info>';
			else if( $file->isDir() ) $size = '<info>[dir]</info>';
			else $size = $this->getSizeUnits($file->getSize());

			$table->addRow([
				$n++,
				$path,
				$size
			]);
		}

		$table->render();
	}

	protected function clean( $removed, array $logs, string $path )
	{
		$this->output->writeln('<info>Clean cache' . $this->getPath($path, " (%s)") . '.</info> Removed files: ' . $removed);
		if(count($logs))
		{
			$this->output->writeln('<error>Remove error:</error>');
			foreach($logs as $log)
			{
				$this->output->writeln((string) $log);
			}
		}
	}

	protected function getPath( string $path, string $mask = "%s" ): string
	{
		if(strlen($path))
		{
			if( DIRECTORY_SEPARATOR !== "/" )
			{
				$path = str_replace(DIRECTORY_SEPARATOR, "/", $path);
			}
			$path = "/" . $path;
			return sprintf($mask, $path);
		}
		else
		{
			return "";
		}
	}

	protected function getSizeUnits(int $size): string
	{
		static $units = [ ' bytes', ' Kb', ' Mb', ' Gb', ' Tb', ' Pb', ' Eb', ' Zb', ' Yb' ];
		if( $size < 1 )
		{
			return '0 bytes';
		}
		if( $size < 1024 )
		{
			return $size . ( $size === 1 ? ' byte' : ' bytes');
		}

		$power = (int) floor( log($size, 1024) );
		return number_format($size / pow(1024, $power), 2, '.', ',') . $units[$power];
	}
}