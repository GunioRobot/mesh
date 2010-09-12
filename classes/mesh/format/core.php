<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mesh Formats.
 * 
 * @package 	Mesh
 * @author 		Suleman Chikhalia
 * @copyright 	(c) Suleman Chikhalia
 * @licence 	MIT
 */
abstract class Mesh_Format_Core {
	
	const ALPHA = 'alpha';
	const ALPHA_SPACE = 'alpha_space';
	const ALPHA_DASH = 'alpha_dash';
	const ALPHA_NUMERIC = 'alpha_numeric';
	const COLOR = 'color';
	const CREDIT_CARD = 'credit_card';
	const DATE = 'date';
	const DECIMAL = 'decimal';
	const DIGIT = 'digit';
	const EMAIL = 'email';
	const EMAIL_DOMAIN = 'email_domain';
	const IP = 'ip';
	const NAME = 'name';
	const NUMERIC = 'numeric';
	const PHONE = 'phone';
	const REGEX = 'regex';
	const SLUG = 'slug';
	const STRING = 'string';
	const TEXT = 'text';
	const URL = 'url';
	const USERNAME = 'username';
	
	public static function text($str)
	{
		// pL matches letters
		// pN matches numbers
		// pZ matches whitespace
		// pPc matches underscores
		// pPd matches dashes
		// pPo matches normal puncuation
		return (bool) preg_match('/^[\pL\pN\pZ\p{Pc}\p{Pd}\p{Po}]++$/uD', (string) $str);
	}
	
	/**
	 * Validate a URL.
	 *
	 * @param   string   URL
	 * @return  boolean
	 */
	public static function url($url)
	{
		return (bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
	}

	/**
	 * Validate an IP.
	 *
	 * @param   string   IP address
	 * @param   boolean  allow private IP networks
	 * @return  boolean
	 */
	public static function ip($ip, $allow_private = TRUE)
	{
		// Do not allow reserved addresses
		$flags = FILTER_FLAG_NO_RES_RANGE;

		if ($allow_private === FALSE)
		{
			// Do not allow private or reserved addresses
			$flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
		}

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
	}

	/**
	 * Validates a credit card number using the Luhn (mod10) formula.
	 *
	 * @link http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param   integer       credit card number
	 * @param   string|array  card type, or an array of card types
	 * @return  boolean
	 */
	public static function credit_card($number, $type = NULL)
	{
		// Remove all non-digit characters from the number
		if (($number = preg_replace('/\D+/', '', $number)) === '')
			return FALSE;

		if ($type == NULL)
		{
			// Use the default type
			$type = 'default';
		}
		elseif (is_array($type))
		{
			foreach ($type as $t)
			{
				// Test each type for validity
				if (Validate::credit_card($number, $t))
					return TRUE;
			}

			return FALSE;
		}

		$cards = Kohana::config('credit_cards');

		// Check card type
		$type = strtolower($type);

		if ( ! isset($cards[$type]))
			return FALSE;

		// Check card number length
		$length = strlen($number);

		// Validate the card length by the card type
		if ( ! in_array($length, preg_split('/\D+/', $cards[$type]['length'])))
			return FALSE;

		// Check card number prefix
		if ( ! preg_match('/^'.$cards[$type]['prefix'].'/', $number))
			return FALSE;

		// No Luhn check required
		if ($cards[$type]['luhn'] == FALSE)
			return TRUE;

		// Checksum of the card number
		$checksum = 0;

		for ($i = $length - 1; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit, starting from the right
			$checksum += substr($number, $i, 1);
		}

		for ($i = $length - 2; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit doubled, starting from the right
			$double = substr($number, $i, 1) * 2;

			// Subtract 9 from the double where value is greater than 10
			$checksum += ($double >= 10) ? $double - 9 : $double;
		}

		// If the checksum is a multiple of 10, the number is valid
		return ($checksum % 10 === 0);
	}

	/**
	 * Checks if a phone number is valid.
	 *
	 * @param   string   phone number to check
	 * @return  boolean
	 */
	public static function phone($number, $lengths = NULL)
	{
		if ( ! is_array($lengths))
		{
			$lengths = array(7,10,11);
		}

		// Remove all non-digit characters from the number
		$number = preg_replace('/\D+/', '', $number);

		// Check if the number is within range
		return in_array(strlen($number), $lengths);
	}

	/**
	 * Tests if a string is a valid date string.
	 *
	 * @param   string   date to check (yyyy-mm-dd)
	 * @return  boolean
	 */
	public static function date($str)
	{
		// validate date format (yyyy-mm-dd)
		if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/D', $str))
		{
			return FALSE;
		}
		
		$date = explode('-', $str);
		
		// check and return date validity
		return checkdate($date[1], $date[2], $date[0]);
	}

	/**
	 * Checks whether a string consists of alphabetical characters only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha($str, $utf8 = FALSE)
	{
		$str = (string) $str;

		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^\pL++$/uD', $str);
		}
		else
		{
			return ctype_alpha($str);
		}
	}
	
	/**
	 * Checks whether a string consists of alphabetical characters and spaces only.
	 * 
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_space($str, $utf8 = FALSE)
	{
		$str = (string) str_replace(' ', '', $str);
		
		return (bool) self::alpha($str, $utf8);
	}

	/**
	 * Checks whether a string consists of alphabetical characters and numbers only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_numeric($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^[\pL\pN]++$/uD', $str);
		}
		else
		{
			return ctype_alnum($str);
		}
	}

	/**
	 * Checks whether a string consists of alphabetical characters, numbers, underscores and dashes only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_dash($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			$regex = '/^[-\pL\pN_]++$/uD';
		}
		else
		{
			$regex = '/^[-a-z0-9_]++$/iD';
		}

		return (bool) preg_match($regex, $str);
	}

	/**
	 * Checks whether a string consists of digits only (no dots or dashes).
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function digit($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^\pN++$/uD', $str);
		}
		else
		{
			return is_int($str) OR ctype_digit($str);
		}
	}

	/**
	 * Checks whether a string is a valid number (negative and decimal numbers allowed).
	 *
	 * Uses {@link http://www.php.net/manual/en/function.localeconv.php locale conversion}
	 * to allow decimal point to be locale specific.
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function numeric($str)
	{
		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^-?[0-9'.$decimal.']++$/D', (string) $str);
	}
	
	/**
	 * Check an email address for correct format.
	 *
	 * @link  http://www.iamcal.com/publish/articles/php/parsing_email/
	 * @link  http://www.w3.org/Protocols/rfc822/
	 *
	 * @param   string   email address
	 * @param   boolean  strict RFC compatibility
	 * @return  boolean
	 */
	public static function email($email, $strict = FALSE)
	{
		if ($strict === TRUE)
		{
			$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
			$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
			$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
			$pair  = '\\x5c[\\x00-\\x7f]';

			$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
			$quoted_string  = "\\x22($qtext|$pair)*\\x22";
			$sub_domain     = "($atom|$domain_literal)";
			$word           = "($atom|$quoted_string)";
			$domain         = "$sub_domain(\\x2e$sub_domain)*";
			$local_part     = "$word(\\x2e$word)*";

			$expression     = "/^$local_part\\x40$domain$/D";
		}
		else
		{
			$expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';
		}

		return (bool) preg_match($expression, (string) $email);
	}

	/**
	 * Validate the domain of an email address by checking if the domain has a
	 * valid MX record.
	 *
	 * @link  http://php.net/checkdnsrr  not added to Windows until PHP 5.3.0
	 *
	 * @param   string   email address
	 * @return  boolean
	 */
	public static function email_domain($email)
	{
		// Check if the email domain has a valid MX record
		return (bool) checkdnsrr(preg_replace('/^[^@]+@/', '', $email), 'MX');
	}
	
	/**
	 * Checks if a string is a proper decimal format. The format array can be
	 * used to specify a decimal length, or a number and decimal length, eg:
	 * array(2) would force the number to have 2 decimal places, array(4,2)
	 * would force the number to have 4 digits and 2 decimal places.
	 *
	 * @param   string   number to check
	 * @param   integer  number of decimal places
	 * @return  boolean
	 */
	public static function decimal($str, $places = 2)
	{
		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^[0-9]+'.preg_quote($decimal).'[0-9]{'.(int) $places.'}$/', $str);
	}

	/**
	 * Checks if a string is a proper hexadecimal HTML color value. The validation
	 * is quite flexible as it does not require an initial "#" and also allows for
	 * the short notation using only three instead of six hexadecimal characters.
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function color($str)
	{
		return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
	}
	
	/**
	 * Checks a field against a regular expression.
	 *
	 * @param   string  value
	 * @param   string  regular expression to match (including delimiters)
	 * @return  boolean
	 */
	public static function regex($value, $expression)
	{
		return (bool) preg_match($expression, (string) $value);
	}
	
	/**
	 * Check a username for the correct format.
	 * 
	 * @param   string  username
	 * @return  boolean
	 */
	public static function username($username)
	{
		return (bool) preg_match('/^[a-z\d_]+$/iD', $username);
	}
	
	/**
	 * Check a name for the correct format.
	 * 
	 * @param   string  name
	 * @return  boolean
	 */
	public static function name($name)
	{
		return (bool) preg_match('/^[a-z-\' ]+$/iD', $name);
	}
	
} // End Mesh Format Core