<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 22:44
 */

namespace EApp\Filesystem;

use EApp\Support\Interfaces\CreateInstanceInterface;
use Traversable;

class Iterator implements \IteratorAggregate, CreateInstanceInterface
{
	const TYPE_FILE = 1;
	const TYPE_DIRECTORY = 2;
	const TYPE_LINK = 4;
	const TYPE_HIDDEN = 8;

	protected $path;

	public function __construct( string $path )
	{
		if( is_dir($path) && ! is_link($path) )
		{
			$this->path = realpath($path);
		}
		else
		{
			throw new Exceptions\InvalidArgumentException("The '{$path}' path does not exist or is not a directory or is a link");
		}
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new \ArrayIterator( $this->getCollection()->getAll() );
	}

	public function getFiles( int $depth = 0, bool $hidden = false ): SplFileCollection
	{
		return $this->create(
			function( \SplFileInfo $file ) use ($hidden) {
				return $file->isFile() && ! $file->isLink() && ( ! $hidden || $file->getBasename()[0] !== "." );
			}, $depth
		);
	}

	public function getDirectories( int $depth = 0, bool $hidden = false ): SplFileCollection
	{
		return $this->create(
			function( \SplFileInfo $file ) use ($hidden) {
				return $file->isDir() && ! $file->isLink() && ( ! $hidden || $file->getBasename()[0] !== "." );
			}, $depth
		);
	}

	public function getRegexpCollection( string $reg_exp, int $depth = 0, int $types = 0 ): SplFileCollection
	{
		return $this->create(
			function( \SplFileInfo $file ) use ($reg_exp) {
				return preg_match( $reg_exp, $file->getBasename() );
			}, $depth, false, $types
		);
	}

	public function getClosureCollection( \Closure $closure, int $depth = 0, int $types = 0 ): SplFileCollection
	{
		return $this->create(
			$closure, $depth, false, $types
		);
	}

	public function getCollection( int $depth = 0, int $types = 0 ): SplFileCollection
	{
		return $this->create(
			static function( \SplFileInfo $file ) { return true; }, $depth, false, $types
		);
	}

	public function each( \Closure $closure, int $depth = 0, int $type = 0 )
	{
		$all = $type === 0;
		$type_file = $all || $type & self::TYPE_FILE;
		$type_directory = $all || $type & self::TYPE_DIRECTORY;
		$type_link = $all || $type & self::TYPE_LINK;
		$type_hidden = $all || $type & self::TYPE_HIDDEN;

		$this->eachOnly($closure, $this->path, 0, $depth, ! $type_file, ! $type_directory, ! $type_link, ! $type_hidden );

		return $this;
	}

	protected function create( \Closure $filter, int $depth, bool $filter_only = true, int $type = 0 ): SplFileCollection
	{
		$collection = new SplFileCollection();

		if( $filter_only ) {
			$this->fill($filter, $collection, $this->path, 0, $depth );
		}
		else {
			$all = $type === 0;
			$type_file = $all || $type & self::TYPE_FILE;
			$type_directory = $all || $type & self::TYPE_DIRECTORY;
			$type_link = $all || $type & self::TYPE_LINK;
			$type_hidden = $all || $type & self::TYPE_HIDDEN;
			$this->fill($filter, $collection, $this->path, 0, $depth, false, ! $type_file, ! $type_directory, ! $type_link, ! $type_hidden );
		}

		return $collection;
	}

	protected function eachOnly( \Closure $closure, string $path, int $depth, int $limit, bool $not_file, bool $not_directory, bool $not_link, bool $not_hidden )
	{
		$iterator = new \FilesystemIterator($path);
		$is_depth = $limit === 0 || $depth < $limit;
		$path .= DIRECTORY_SEPARATOR;

		foreach( $iterator as $file )
		{
			! (
				$not_file && $file->isFile() ||
				$not_directory && $file->isDir() ||
				$not_link && $file->isLink() ||
				$not_hidden && $file->getBasename()[0] === "." ) && $closure($file, $depth + 1, $this->path );

			if( $is_depth && $file->isDir() && ! $file->isLink() )
			{
				$this->eachOnly(
					$closure,
					$path . $file->getBasename(),
					$depth + 1,
					$limit,
					$not_file,
					$not_directory,
					$not_link,
					$not_hidden
				);
			}
		}
	}

	protected function fill( \Closure $filter, SplFileCollection $collection, string $path, int $depth, int $limit, bool $filter_only = true, bool $not_file = true, bool $not_directory = true, bool $not_link = true, bool $not_hidden = true )
	{
		$iterator = new \FilesystemIterator($path);
		$is_depth = $limit === 0 || $depth < $limit;
		$path .= DIRECTORY_SEPARATOR;

		/** @var \SplFileInfo $file */
		if( $filter_only )
		{
			foreach( $iterator as $file )
			{
				if( $filter($file) )
				{
					$collection[] = $file;
				}

				if( $is_depth && $file->isDir() && ! $file->isLink() )
				{
					$this->fill(
						$filter,
						$collection,
						$path . $file->getBasename(),
						$depth + 1,
						$limit,
						true
					);
				}
			}
		}
		else
		{
			foreach( $iterator as $file )
			{
				$valid = ! (
					$not_file && $file->isFile() ||
					$not_directory && $file->isDir() ||
					$not_link && $file->isLink() ||
					$not_hidden && $file->getBasename()[0] === "." ) && $filter($file, $depth + 1, $this->path );

				if( $valid )
				{
					$collection[] = $file;
				}

				if( $is_depth && $file->isDir() && ! $file->isLink() && ($not_directory || $valid) )
				{
					$this->fill(
						$filter,
						$collection,
						$path . $file->getBasename(),
						$depth + 1,
						$limit,
						false,
						$not_file,
						$not_directory,
						$not_link,
						$not_hidden
					);
				}
			}
		}
	}

	/**
	 * @param array ...$args
	 * @return Iterator
	 */
	public static function createInstance( ... $args )
	{
		return new self( ... $args );
	}
}