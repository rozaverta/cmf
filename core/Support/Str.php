<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 10:55
 */

namespace EApp\Support;

class Str
{
	protected static $cache = [];

	/**
	 * Convert a value to camel case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function camel($value)
	{
		return lcfirst(static::studly($value));
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function studly($value)
	{
		return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function kebab($value)
	{
		return static::snake($value, '-');
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	public static function snake($value, $delimiter = '_')
	{
		if(! ctype_lower($value))
		{
			$value = preg_replace('/\s+/u', '', ucwords($value));
			$value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
		}
		return $value;
	}

	/**
	 * Convert the given string to lower-case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function lower($value)
	{
		return mb_strtolower($value, BASE_ENCODING);
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function upper($value)
	{
		return mb_strtoupper($value, BASE_ENCODING);
	}

	/**
	 * Convert the given string to title case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function title($value)
	{
		return mb_convert_case($value, MB_CASE_TITLE, BASE_ENCODING);
	}

	/**
	 * Convert the given string to upper-case.
	 *
	 * @param  string  $value
	 * @return int
	 */
	public static function len($value)
	{
		return mb_strlen($value, BASE_ENCODING);
	}

	/**
	 * Get part of string
	 *
	 * @link http://php.net/manual/en/function.mb-substr.php
	 *
	 * @param string $value The string being checked.
	 * @param int $start The first position used in str.
	 * @param int $length [optional] The maximum length of the returned string.
	 * @return string returns the portion of str specified by the start and length parameters.
	 */
	public static function cut($value, $start, $length = null)
	{
		return mb_substr($value, $start, $length, BASE_ENCODING);
	}

	/**
	 * Read the given string from cache or convert to needle case and save to cache.
	 *
	 * @param string $value
	 * @param string $name
	 * @param string $delimiter used only snake case
	 * @return string
	 */
	public static function cache( $value, $name, $delimiter = null )
	{
		if( $value === '' )
		{
			return $value;
		}

		$func = $name;
		$arg2 = $name == "snake";

		if( $arg2 )
		{
			if( is_null($delimiter) )
			{
				$delimiter = "_";
			}
		}
		else if( ($arg2 = $arg2 == "ascii") )
		{
			if( is_null($delimiter) )
			{
				$delimiter = "en";
			}
		}

		if( $arg2 )
		{
			$name .= $delimiter;
		}

		if( isset(self::$cache[$name][$value]) )
		{
			return self::$cache[$name][$value];
		}

		return self::$cache[$name][$value] = ( $func == "snake" ? static::snake($value, $delimiter) : static::$func($value) );
	}

	public static function random( $strLen = 10, $digit = true, $symbol = false, $ext = "" )
	{
		static $alpha = "abcdefjhigklmnopqrstuvwxyzABCDEFJHIGKLMNOPQRSTUVWXYZ";

		$word = $alpha;
		if( $digit )  $word .= "0123456789";
		if( $symbol ) $word .= ',.!?:;@#$%^&*()[]{}-_=+~`\\/<>';
		if( $ext )    $word .= $ext;

		$len = strlen( $word ) - 1;
		$strLen = (int) $strLen;
		if( $strLen < 1 ) $strLen = 1;
		else if( $strLen > 10000 ) $strLen = 10000;

		$get = "";
		for( $i = 0; $i < $strLen; $i++ )
		{
			$get .= $word[ mt_rand( 0, $len ) ];
		}

		return $get;
	}

	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needles
	 * @return bool
	 */
	public static function contains( $haystack, $needles )
	{
		foreach((array) $needles as $needle)
		{
			if ($needle !== '' && mb_strpos($haystack, $needle) !== false)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Replace a given value in the string sequentially with an array.
	 *
	 * @param  string  $search
	 * @param  array   $replace
	 * @param  string  $subject
	 * @return string
	 */
	public static function replaceArray($search, array $replace, $subject)
	{
		foreach ($replace as $value)
		{
			$subject = static::replaceFirst($search, $value, $subject);
		}
		return $subject;
	}

	/**
	 * Replace the first occurrence of a given value in the string.
	 *
	 * @param  string  $search
	 * @param  string  $replace
	 * @param  string  $subject
	 * @return string
	 */
	public static function replaceFirst($search, $replace, $subject)
	{
		if ($search == '')
		{
			return $subject;
		}

		$position = strpos($subject, $search);
		if ($position !== false)
		{
			return substr_replace($subject, $replace, $position, strlen($search));
		}

		return $subject;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needles
	 * @return bool
	 */
	public static function endsWith($haystack, $needles)
	{
		foreach ((array) $needles as $needle)
		{
			if (substr($haystack, -strlen($needle)) === (string) $needle) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param  string  $value
	 * @param  string  $language
	 * @return string
	 */
	public static function ascii($value, $language = 'en')
	{
		$languageSpecific = static::languageSpecificCharsArray($language);

		if (! is_null($languageSpecific))
		{
			$value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
		}

		foreach (static::charsArray() as $key => $val)
		{
			$value = str_replace($val, $key, $value);
		}

		return preg_replace('/[^\x20-\x7E]/u', '', $value);
	}

	/**
	 * Returns the language specific replacements for the ascii method.
	 *
	 * Note: Adapted from Stringy\Stringy.
	 *
	 * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
	 *
	 * @param  string  $language
	 * @return array|null
	 */
	protected static function languageSpecificCharsArray($language)
	{
		static $languageSpecific;

		if (! isset($languageSpecific))
		{
			$languageSpecific = [
				'bg' => [
					['х', 'Х', 'щ', 'Щ', 'ъ', 'Ъ', 'ь', 'Ь'],
					['h', 'H', 'sht', 'SHT', 'a', 'А', 'y', 'Y'],
				],
				'de' => [
					['ä',  'ö',  'ü',  'Ä',  'Ö',  'Ü'],
					['ae', 'oe', 'ue', 'AE', 'OE', 'UE'],
				],
			];
		}

		return isset($languageSpecific[$language]) ? $languageSpecific[$language] : null;
	}

	/**
	 * Returns the replacements for the ascii method.
	 *
	 * Note: Adapted from Stringy\Stringy.
	 *
	 * @see https://github.com/danielstjules/Stringy/blob/3.1.0/LICENSE.txt
	 *
	 * @return array
	 */
	protected static function charsArray()
	{
		static $charsArray;

		if (isset($charsArray))
		{
			return $charsArray;
		}

		return $charsArray = [
			'0'    => ['°', '₀', '۰', '０'],
			'1'    => ['¹', '₁', '۱', '１'],
			'2'    => ['²', '₂', '۲', '２'],
			'3'    => ['³', '₃', '۳', '３'],
			'4'    => ['⁴', '₄', '۴', '٤', '４'],
			'5'    => ['⁵', '₅', '۵', '٥', '５'],
			'6'    => ['⁶', '₆', '۶', '٦', '６'],
			'7'    => ['⁷', '₇', '۷', '７'],
			'8'    => ['⁸', '₈', '۸', '８'],
			'9'    => ['⁹', '₉', '۹', '９'],
			'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä'],
			'b'    => ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ'],
			'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
			'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ'],
			'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
			'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ'],
			'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ'],
			'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
			'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ'],
			'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
			'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ'],
			'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ'],
			'm'    => ['м', 'μ', 'م', 'မ', 'მ', 'ｍ'],
			'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ'],
			'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
			'p'    => ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ'],
			'q'    => ['ყ', 'ｑ'],
			'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ'],
			's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ'],
			't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ'],
			'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
			'v'    => ['в', 'ვ', 'ϐ', 'ｖ'],
			'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
			'x'    => ['χ', 'ξ', 'ｘ'],
			'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
			'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ'],
			'aa'   => ['ع', 'आ', 'آ'],
			'ae'   => ['æ', 'ǽ'],
			'ai'   => ['ऐ'],
			'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
			'dj'   => ['ђ', 'đ'],
			'dz'   => ['џ', 'ძ'],
			'ei'   => ['ऍ'],
			'gh'   => ['غ', 'ღ'],
			'ii'   => ['ई'],
			'ij'   => ['ĳ'],
			'kh'   => ['х', 'خ', 'ხ'],
			'lj'   => ['љ'],
			'nj'   => ['њ'],
			'oe'   => ['ö', 'œ', 'ؤ'],
			'oi'   => ['ऑ'],
			'oii'  => ['ऒ'],
			'ps'   => ['ψ'],
			'sh'   => ['ш', 'შ', 'ش'],
			'shch' => ['щ'],
			'ss'   => ['ß'],
			'sx'   => ['ŝ'],
			'th'   => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
			'ts'   => ['ц', 'ც', 'წ'],
			'ue'   => ['ü'],
			'uu'   => ['ऊ'],
			'ya'   => ['я'],
			'yu'   => ['ю'],
			'zh'   => ['ж', 'ჟ', 'ژ'],
			'(c)'  => ['©'],
			'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
			'B'    => ['Б', 'Β', 'ब', 'Ｂ'],
			'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
			'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
			'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
			'F'    => ['Ф', 'Φ', 'Ｆ'],
			'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
			'H'    => ['Η', 'Ή', 'Ħ', 'Ｈ'],
			'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
			'J'    => ['Ｊ'],
			'K'    => ['К', 'Κ', 'Ｋ'],
			'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
			'M'    => ['М', 'Μ', 'Ｍ'],
			'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
			'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
			'P'    => ['П', 'Π', 'Ｐ'],
			'Q'    => ['Ｑ'],
			'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
			'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
			'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
			'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
			'V'    => ['В', 'Ｖ'],
			'W'    => ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
			'X'    => ['Χ', 'Ξ', 'Ｘ'],
			'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
			'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
			'AE'   => ['Æ', 'Ǽ'],
			'Ch'   => ['Ч'],
			'Dj'   => ['Ђ'],
			'Dz'   => ['Џ'],
			'Gx'   => ['Ĝ'],
			'Hx'   => ['Ĥ'],
			'Ij'   => ['Ĳ'],
			'Jx'   => ['Ĵ'],
			'Kh'   => ['Х'],
			'Lj'   => ['Љ'],
			'Nj'   => ['Њ'],
			'Oe'   => ['Œ'],
			'Ps'   => ['Ψ'],
			'Sh'   => ['Ш'],
			'Shch' => ['Щ'],
			'Ss'   => ['ẞ'],
			'Th'   => ['Þ'],
			'Ts'   => ['Ц'],
			'Ya'   => ['Я'],
			'Yu'   => ['Ю'],
			'Zh'   => ['Ж'],
			' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80", "\xEF\xBE\xA0"],
		];
	}
}