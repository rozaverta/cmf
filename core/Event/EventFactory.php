<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace EApp\Event;

use EApp\App;
use EApp\DB\Query\JoinClause;
use EApp\Event\Interfaces\EventPrepareInterface;
use EApp\Component\Module;

final class EventFactory
{
	private $id = 0;
	private $name = '';
	private $completable = false;
	private $classes = [];

	public function __construct( $name )
	{
		$this->name = trim($name);

		$row = \DB::table('events')
			->limit(1)
			->where('name', $this->name)
			->first(['id', 'completable']);

		if( $row )
		{
			$this->id = (int) $row->id;
			$this->completable = $row->completable > 0;
		}
	}

	public function load()
	{
		if( !$this->id )
		{
			return false;
		}

		$rows = \DB::table('event_callback as c')
			->leftJoin('event_lnk as lnk', function( JoinClause $join ) {
				$join->on('c.id', '=', 'lnk.callback_id');
			})
			->where('lnk.event_id', $this->id)
			->orderBy('c.priority')
			->groupBy('c.id')
			->get(
				[ 'c.module_id', 'c.priority', 'c.class_name' ]
			);

		foreach( $rows as $row )
		{
			$class_name = $row->class_name;
			if( $class_name[0] !== "\\" && $row->module_id > 0 )
			{
				$class_name = Module::cache((int) $row->module_id)->get("name_space") . $class_name;
			}
			if( $class_name[0] !== "\\" )
			{
				$class_name = "\\" . $class_name;
			}

			if( ! class_exists($class_name, true) )
			{
				$this->logLine("Prepared class '{$class_name}' not found.");
			}
			else if( ! (new \ReflectionClass($class_name))->implementsInterface( EventPrepareInterface::class ) )
			{
				$this->logLine("Class '{$class_name}' should implement the interface \\EApp\\Event\\Interfaces\\EventPrepareInterface.");
			}
			else
			{
				$this->classes[] = $class_name;
			}
		}

		return count($this->classes);
	}

	public function getContentData()
	{
		return
			[
				"id" => $this->id,
				"name" => $this->name,
				"completable" => $this->completable,
				"classes" => $this->classes
			];
	}

	private function logLine( $line )
	{
		App::Log()->line($line);
	}
}