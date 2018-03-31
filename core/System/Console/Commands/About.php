<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\Console\Commands;

use EApp\App;
use EApp\Console\ConsoleCommand;
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
			$output->writeln('<comment>' . $com->get(0) . '</>');
		}

		$output->writeln('<info>Name:</>     ' . $cnf->getOr( "name","Elastic CMF"));
		$output->writeln('<info>Version:</>  ' . $cnf->getOr( "version", App::VER));

		if( $com->getIs("author") )
		{
			$output->writeln('<info>Author:</>   ' . $com->get("author"));
		}
		if( $com->getIs("date") )
		{
			$output->writeln('<info>Date:</>     ' . $com->get("date"));
		}

		if( SYSTEM_INSTALL )
		{
			// TODO get modules info
		}
		else
		{
			$output->writeln('<error>System is not install</>');
		}
	}
}
