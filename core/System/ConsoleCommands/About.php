<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\System\ConsoleCommands;

use EApp\App;
use EApp\Proto\ConsoleCommand;
use EApp\Prop;
use EApp\System\Files\Php\DocComments;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get system info
 *
 * @package EApp\Console
 */
class About extends ConsoleCommand
{
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$els = App::getInstance();
		$com = new DocComments($els);
		$cnf = Prop::cache("system");

		if( $com->getIs(0) )
		{
			$output->writeln('<comment>' . $com->get(0) . '</comment>');
		}

		$output->writeln('<info>Name:</info>     ' . $cnf->getOr( "name","Elastic CMF"));
		$output->writeln('<info>Version:</info>  ' . $cnf->getOr( "version", App::VER));

		if( $com->getIs("author") )
		{
			$output->writeln('<info>Author:</info>   ' . $com->get("author"));
		}
		if( $com->getIs("date") )
		{
			$output->writeln('<info>Date:</info>     ' . $com->get("date"));
		}

		// - system --manifest
		// - system --install
		// - system --update
		// - system --remove    --> enter system key SYSTEM_KEY

		// - module --add Module/NameSpace
		// - module --install  key
		// - module --update   key
		// - module --remove   key
		// - module --list
		// - module --info     key
		// - module --manifest key

		// - cache  --clean [type/directory], --size

		// - event | route | package | plugin | config | database --list

		if( SYSTEM_INSTALL )
		{
			// TODO get modules info
		}
		else
		{
			$output->writeln('<error>System is not install</error>');
		}
	}
}
