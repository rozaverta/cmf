<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 21:55
 *
 * Used source from laravel/framework -> Illuminate\Filesystem\Filesystem
 */

namespace EApp\Filesystem;

use EApp\Filesystem\Exceptions\NotFoundFileException;
use EApp\Helper;
use EApp\Traits\CallbackTrait;
use EApp\Traits\SingletonInstanceTrait;
use ErrorException;
use FilesystemIterator;

class Filesystem
{
	use SingletonInstanceTrait;
	use CallbackTrait;

	/**
	 * Determine if a file or directory exists.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function exists( $path )
	{
		return file_exists( $path );
	}

	/**
	 * Get the contents of a file
	 *
	 * @param  string $path
	 * @param  bool $lock
	 * @return string
	 *
	 * @throws Exceptions\NotFoundFileException
	 */
	public function get( string $path, bool $lock = false )
	{
		if( $this->isFile( $path ) )
		{
			return $lock ? $this->sharedGet( $path ) : file_get_contents( $path );
		}
		throw new NotFoundFileException($path, "File does not exist at path '{$path}'");
	}

	/**
	 * Get contents of a file with shared access.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function sharedGet( $path )
	{
		$contents = '';
		$handle = fopen( $path, 'rb' );
		if( $handle )
		{
			try {
				if( flock( $handle, LOCK_SH ) )
				{
					clearstatcache( true, $path );
					$contents = fread( $handle, $this->size( $path ) ?: 1 );
					flock( $handle, LOCK_UN );
				}
			}
			finally {
				fclose( $handle );
			}
		}
		return $contents;
	}

	/**
	 * Get the returned value of a file
	 *
	 * @param  string $path
	 * @return mixed
	 *
	 * @throws NotFoundFileException
	 */
	public function getRequire( string $path )
	{
		if( $this->isFile( $path ) )
		{
			return $this->callback(static function($file) {
				/** @noinspection PhpIncludeInspection */
				return require $file;
			}, $path);
		}
		throw new NotFoundFileException($path,"File does not exist at path '{$path}'" );
	}

	/**
	 * Get the saved data value of a file
	 *
	 * @param  string $path
	 * @param array $default
	 * @return mixed
	 *
	 * @throws NotFoundFileException
	 */
	public function getRequireData( string $path, $default = [] )
	{
		if( $this->isFile( $path ) )
		{
			return $this->callback(function($file) use($default) {
				/** @noinspection PhpIncludeInspection */
				require $file;
				return $data ?? $default ?? null;
			}, $path);
		}
		throw new NotFoundFileException($path, "File does not exist at path '{$path}'");
	}

	/**
	 * Get the MD5 hash of the file at the given path
	 *
	 * @param  string $path
	 * @return string
	 */
	public function hash( $path )
	{
		return md5_file( $path );
	}

	/**
	 * Write the contents of a file
	 *
	 * @param  string $path
	 * @param  string $contents
	 * @param  bool $lock
	 * @return int
	 */
	public function put( string $path, string $contents, bool $lock = false )
	{
		return file_put_contents( $path, $contents, $lock ? LOCK_EX : 0 );
	}

	/**
	 * Prepend to a file
	 *
	 * @param  string $path
	 * @param  string $data
	 * @return int
	 */
	public function prepend( string $path, string $data )
	{
		if( $this->exists( $path ) )
		{
			return $this->put( $path, $data . $this->get( $path ) );
		}
		return $this->put( $path, $data );
	}

	/**
	 * Append to a file
	 *
	 * @param  string $path
	 * @param  string $data
	 * @return int
	 */
	public function append( string $path, string $data )
	{
		return file_put_contents( $path, $data, FILE_APPEND );
	}

	/**
	 * Get or set UNIX mode of a file or directory
	 *
	 * @param  string $path
	 * @param  int $mode
	 * @return mixed
	 */
	public function chmod( $path, $mode = null )
	{
		if( $mode )
		{
			return chmod( $path, $mode );
		}
		return substr( sprintf( '%o', fileperms( $path ) ), -4 );
	}

	/**
	 * Delete the file at a given path
	 *
	 * @param array $paths
	 * @return bool
	 */
	public function delete( ... $paths ): bool
	{
		$all = 0;
		$removed = 0;
		if( count($paths) === 1 && is_array($paths[0]) )
		{
			$paths = $paths[0];
		}

		foreach( $paths as $path )
		{
			++ $all;
			$this->deleteOnce( (string) $path ) && ++ $removed;
		}

		return $all === $removed;
	}

	/**
	 * Delete the file at a given path
	 *
	 * @param string $path
	 * @return bool
	 */
	public function deleteOnce( string $path ): bool
	{
		try {
			if( ! @ unlink( $path ) ) throw new ErrorException;
		}
		catch( ErrorException $e )
		{
			return false;
		}

		return true;
	}

	/**
	 * Move a file to a new location.
	 *
	 * @param  string $path
	 * @param  string $target
	 * @return bool
	 */
	public function move( $path, $target )
	{
		return rename( $path, $target );
	}

	/**
	 * Copy a file to a new location.
	 *
	 * @param  string $path
	 * @param  string $target
	 * @return bool
	 */
	public function copy( $path, $target )
	{
		return copy( $path, $target );
	}

	/**
	 * Create a hard link to the target file or directory.
	 *
	 * @param  string $target
	 * @param  string $link
	 * @return void
	 */
	public function link( string $target, string $link )
	{
		if( Helper::isOsWindows() )
		{
			$mode = $this->isDirectory( $target ) ? 'J' : 'H';
			exec( "mklink /{$mode} \"{$link}\" \"{$target}\"" );
		}
		else
		{
			symlink( $target, $link );
		}
	}

	/**
	 * Extract the file name from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function name( string $path )
	{
		return pathinfo( $path, PATHINFO_FILENAME );
	}

	/**
	 * Extract the trailing name component from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function basename( string $path )
	{
		return pathinfo( $path, PATHINFO_BASENAME );
	}

	/**
	 * Extract the parent directory from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function dirname( string $path )
	{
		return pathinfo( $path, PATHINFO_DIRNAME );
	}

	/**
	 * Extract the file extension from a file path.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function extension( string $path )
	{
		return pathinfo( $path, PATHINFO_EXTENSION );
	}

	/**
	 * Get the file type of a given file.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function type( string $path ): string
	{
		return filetype( $path );
	}

	/**
	 * Get the mime-type of a given file.
	 *
	 * @param  string $path
	 * @return string|false
	 */
	public function mimeType( $path )
	{
		return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $path );
	}

	/**
	 * Get the file size of a given file.
	 *
	 * @param  string $path
	 * @return int
	 */
	public function size( $path )
	{
		return filesize( $path );
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @param  string $path
	 * @return int
	 */
	public function lastModified( string $path )
	{
		return filemtime( $path );
	}

	/**
	 * Determine if the given path is a directory.
	 *
	 * @param  string $directory
	 * @return bool
	 */
	public function isDirectory( string $directory ): bool
	{
		return is_dir( $directory );
	}

	/**
	 * Determine if the given path is readable.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function isReadable( string $path ): bool
	{
		return is_readable( $path );
	}

	/**
	 * Determine if the given path is writable.
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function isWritable( string $path ): bool
	{
		return is_writable( $path );
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param  string $file
	 * @return bool
	 */
	public function isFile( string $file ): bool
	{
		return is_file( $file );
	}

	/**
	 * Create a directory.
	 *
	 * @param  string $path
	 * @param  int $mode
	 * @param  bool $recursive
	 * @param  bool $force
	 * @return bool
	 */
	public function makeDirectory( $path, $mode = 0755, $recursive = false, $force = false )
	{
		if( $force ) {
			return @mkdir( $path, $mode, $recursive );
		}
		return mkdir( $path, $mode, $recursive );
	}

	/**
	 * Move a directory.
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  bool $overwrite
	 * @return bool
	 */
	public function moveDirectory( $from, $to, $overwrite = false ): bool
	{
		if( $overwrite && $this->isDirectory( $to ) && ! $this->deleteDirectory( $to ) )
		{
			return false;
		}
		return @ rename( $from, $to ) === true;
	}

	/**
	 * Copy a directory from one location to another.
	 *
	 * @param  string $directory
	 * @param  string $destination
	 * @param  int $options
	 * @return bool
	 */
	public function copyDirectory( string $directory, string $destination, $options = null ): bool
	{
		if( !$this->isDirectory( $directory ) )
		{
			return false;
		}

		$options = $options ?: FilesystemIterator::SKIP_DOTS;

		// If the destination directory does not actually exist, we will go ahead and
		// create it recursively, which just gets the destination prepared to copy
		// the files over. Once we make the directory we'll proceed the copying.
		if( !$this->isDirectory( $destination ) )
		{
			$this->makeDirectory( $destination, 0777, true );
		}

		$items = new FilesystemIterator( $directory, $options );
		foreach( $items as $item )
		{
			// As we spin through items, we will check to see if the current file is actually
			// a directory or a file. When it is actually a directory we will need to call
			// back into this function recursively to keep copying these nested folders.
			$target = $destination . '/' . $item->getBasename();
			if( $item->isDir() )
			{
				$path = $item->getPathname();
				if( !$this->copyDirectory( $path, $target, $options ) )
				{
					return false;
				}
			}

			// If the current items is just a regular file, we will just copy this to the new
			// location and keep looping. If for some reason the copy fails we'll bail out
			// and return false, so the developer is aware that the copy process failed.
			else
			{
				if( !$this->copy( $item->getPathname(), $target ) )
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * The directory itself may be optionally preserved.
	 *
	 * @param  string $directory
	 * @param  bool $preserve
	 * @return bool
	 */
	public function deleteDirectory( string $directory, $preserve = false )
	{
		if( !$this->isDirectory( $directory ) )
		{
			return false;
		}

		$items = new FilesystemIterator( $directory );
		foreach( $items as $item )
		{
			// If the item is a directory, we can just recurse into the function and
			// delete that sub-directory otherwise we'll just delete the file and
			// keep iterating through each file until the directory is cleaned.
			if( $item->isDir() && !$item->isLink() )
			{
				$this->deleteDirectory( $item->getPathname() );
			}

			// If the item is just a file, we can go ahead and delete it since we're
			// just looping through and waxing all of the files in this directory
			// and calling directories recursively, so we delete the real path.
			else
			{
				$this->deleteOnce( $item->getPathname() );
			}
		}

		if( ! $preserve )
		{
			@ rmdir( $directory );
		}

		return true;
	}

	/**
	 * Remove all of the directories within a given directory.
	 *
	 * @param  string $directory
	 * @return bool
	 */
	public function deleteDirectories( string $directory )
	{
		try {
			$allDirectories = Iterator::createInstance( $directory )->getDirectories();
		}
		catch( Exceptions\InvalidArgumentPathException $e ) { return false; }

		if( $allDirectories->isNotEmpty() )
		{
			/** @var \SplFileInfo $directoryName */
			foreach( $allDirectories as $directoryName )
			{
				$this->deleteDirectory( $directoryName->getPathname() );
			}
			return true;
		}

		return false;
	}

	/**
	 * Empty the specified directory of all files and folders.
	 *
	 * @param  string $directory
	 * @return bool
	 */
	public function cleanDirectory( $directory )
	{
		return $this->deleteDirectory( $directory, true );
	}
}