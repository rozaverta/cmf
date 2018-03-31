<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 15:59
 */

namespace EApp\CI;

use EApp\App;
use EApp\Event\EventManager;
use EApp\Language\Interfaces\I18Interface;
use EApp\Language\Interfaces\TextInterface;
use EApp\Language\Interfaces\TransliterationInterface;
use EApp\Language\Language;
use EApp\Language\LanguageFiles;
use EApp\Support\Interfaces\SingletonCompletable;
use EApp\Support\Str;
use EApp\Support\Traits\SingletonInstance;
use EApp\System\Events\LanguageEvent;

/**
 * Class Json
 * @package CI
 * @method static Lang getInstance()
 */
final class Lang implements SingletonCompletable
{
	use SingletonInstance;

	/**
	 * Current language
	 *
	 * @var string
	 */
	private $language = null;

	/**
	 * @var Language | I18Interface | TextInterface | TransliterationInterface
	 */
	private $lang;

	private $lang_i18 = false;
	private $lang_transliteration = false;
	private $lang_text = false;
	private $lang_default = false;

	protected function __construct()
	{
		// set default
		$this->language = App::Config("language", "en");
	}

	public function instanceComplete( App $app )
	{
		static $complete = false;
		if( $complete )
		{
			throw new \Exception("The language pack instance has already been completed.");
		}

		$complete = true;
		$language = $this->language;
		$this->language = null;

		EventManager::dispatch(
			'onLanguage',
			new LanguageEvent($this, $language, 'onLanguage'),
			function ( $result ) {
				return is_string($result) && $this->reload($result) ? false : null;
			});

		if( $this->language === null )
		{
			$this->reload($language);
		}
	}

	public function text( $text )
	{
		if( $this->lang_text )
		{
			return $this->lang->transliterate($text);
		}
		else
		{
			return $text;
		}
	}

	public function current()
	{
		return $this->language;
	}

	public function currentIsDefault()
	{
		return $this->lang_default;
	}

	public function load( $name )
	{
		return $this->lang->load($name);
	}

	public function reload( $language )
	{
		$language = trim( $language );
		if( ! $language )
		{
			return false;
		}

		if( $this->language === $language )
		{
			return true;
		}

		$packages = is_null($this->lang) ? [] : $this->lang->packages();
		$this->language = $language;
		$this->lang = null;

		EventManager::dispatch(
			'onLanguageLoad',
			new LanguageEvent($this, $language, 'onLanguageLoad'),
			function ( $result ) {
				if( $result instanceof Language )
				{
					$this->lang = $result;
					return false;
				}
			});

		if( is_null($this->lang) )
		{
			$this->lang = new LanguageFiles($language);
		}

		$this->lang_i18 = $this->lang instanceof I18Interface;
		$this->lang_transliteration = $this->lang instanceof TransliterationInterface;
		$this->lang_text = $this->lang instanceof TextInterface;
		$this->lang_default = $this->lang->isDefault();

		foreach($packages as $package)
		{
			$this->lang->load($package);
		}

		return true;
	}

	public function item( $name, $default = '' )
	{
		return isset( $this->lang->keys[$name] ) ? $this->lang->keys[$name] : $default;
	}

	public function line( $text )
	{
		if( $this->lang_default )
		{
			return $text;
		}

		if( isset( $this->lang->lines[$text] ) )
		{
			return $this->lang->lines[$text];
		}

		$lower = Str::lower( $text );
		$lower = trim( $lower );
		$lower = preg_replace( '/\s{2,}/', " ", $lower );

		if( isset( $this->lang->lines[$lower] ) )
		{
			return $this->lang->lines[$lower];
		}
		else
		{
			return $text;
		}
	}

	public function replace( $text, $replace )
	{
		if( ! $this->lang_default )
		{
			$text = $this->line( $text );
		}

		if( !is_array( $replace ) )
		{
			$replace = [$replace];
		}

		$new_text = "";
		$num = 0;
		$len = count($replace);

		for(;;)
		{
			$pos = strpos( $text, "%", 0 );
			if( $pos === false )
			{
				$new_text .= $text;
				break;
			}

			if( $pos > 0 )
			{
				$new_text .= substr( $text, 0, $pos );
			}

			if( strlen( $text ) < 2 )
			{
				break;
			}

			$text = substr( $text, $pos + 1 );
			if( $text[0] == "s" )
			{
				$new_text .= $num < $len ? @ $replace[$num++] : "";
				$text = substr( $text, 1 );
			}

			else if( $text[0] == "d" )
			{
				$i18n = $num < $len ? @ (int) $replace[$num++] : 0;
				if( preg_match( '/^d-\((.*?)\)/', $text, $m ) )
				{
					$new_text .= $this->i18n( $i18n, trim( $m[1] ) );
					$text = substr( $text, strlen( $m[0] ) );
				}
				else
				{
					$new_text .= $i18n;
					$text = substr( $text, 1 );
				}
			}

			else if( preg_match( '/^(\d+)(s|d)/', $text, $m ) )
			{
				$int = (int) $m[1];
				if( $int > 0 ) $int --;
				$val = isset( $replace[$int] ) ? $replace[$int] : "";

				$text = substr( $text, strlen( $m[0] ) );
				if( $m[2] == "s" )
				{
					$new_text .= $val;
				}
				else
				{
					$val = (int) $val;
					if( preg_match( '/^-\((.*?)\)/', $text, $m ) )
					{
						$val = $this->i18n( $val, $m[1] );
						$text = substr( $text, strlen( $m[0] ) );
					}
					$new_text .= $val;
				}
			}
		}

		return $new_text;
	}

	public function i18n( $number, $string, $replace = null )
	{
		if( ! is_array( $string ) )
		{
			if( isset( $this->lang->keys[$string] ) )
			{
				$string = $this->lang->keys[$string];
			}
			else if( $this->lang_i18 )
			{
				return $this->lang->i18Invoke($replace, $number);
			}
		}

		$number = (int) $number;
		if( is_array( $string ) )
		{
			if( $this->lang_i18 )
			{
				$string = $this->lang->i18($number, $string);
			}
			else
			{
				$string = reset($string);
			}
		}

		$pos = strpos( $string, "%d" );
		if( $pos !== false )
		{
			$string = substr_replace( $string, $number, $pos, 2 );
		}

		if( $replace )
		{
			$string = $this->replace( $string, $replace );
		}

		return $string;
	}

	public function transliterate( $word, $latinOnly = true )
	{
		if( $this->lang_transliteration )
		{
			$word = $this->lang->transliterate($word);
		}
		else
		{
			$word = Str::ascii($word, $this->language);
		}

		if( $latinOnly )
		{
			$word = preg_replace('/[^\x00-\xff]+/u', '', $word);
			$word = trim( preg_replace('/\s+/', ' ', $word ) );
		}

		return $word;
	}
}