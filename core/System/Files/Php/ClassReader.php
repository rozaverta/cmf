<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\System\Files\Php;

use EApp\Support\Traits\Get;
use EApp\System\Files\Php\DocComments;

/**
 * Class Help
 * @package EApp\Console
 */
class ClassReader
{
	use Get;

	protected $items = [];

	private $file;
	private $name = null;
	private $class_name = null;

	public function __construct( $file )
	{
		if( !file_exists($file) )
		{
			throw new \Exception("File not found '{$file}'");
		}

		$this->file = $file;
	}

	public function ready()
	{
		$class = $this->getClass();
		if( !$class || !class_exists($class, true) )
		{
			return false;
		}

		$rc = new \ReflectionClass($class);
		if( $rc->isAbstract() || ! $rc->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') )
		{
			return false;
		}

		$docs = new DocComments($rc);
		$this->items = $docs->getAll();

		if( !isset($this->items["name"]) )
		{
			$this->items["name"] = $this->name;
		}

		return true;
	}

	public function getClassName()
	{
		return $this->class_name;
	}

	private function getClass()
	{
		$fp = @ fopen($this->file, 'r');
		if( !$fp )
		{
			throw new \Exception("Can't ready file '{$this->file}'");
		}

		$class = $namespace = $buffer = '';
		$i = 0;

		while(!$class)
		{
			if(feof($fp))
			{
				break;
			}

			$buffer .= fread($fp, 512);
			$tokens  = token_get_all($buffer);

			if(strpos($buffer, '{') === false)
			{
				continue;
			}

			$count = count($tokens);

			for(;$i<$count;$i++)
			{
				if($tokens[$i][0] === T_NAMESPACE)
				{
					for($j = $i+1; $j<$count; $j++)
					{
						if ($tokens[$j][0] === T_STRING)
						{
							$namespace .= '\\'.$tokens[$j][1];
						}
						else if ($tokens[$j] === '{' || $tokens[$j] === ';')
						{
							break;
						}
					}
				}

				if($tokens[$i][0] === T_CLASS)
				{
					for($j = $i+1; $j<$count; $j++)
					{
						if ($tokens[$j] === '{')
						{
							$class = $tokens[$i+2][1];
						}
					}
				}
			}
		}

		@ fclose($fp);

		if( ! $class )
		{
			return false;
		}

		$this->name = $class;
		$this->class_name = ( $namespace ? ($namespace . "\\") : "" ) . $class;

		return $this->class_name;
	}
}
