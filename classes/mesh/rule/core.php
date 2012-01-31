<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mesh Rules.
 *
 * @package 	Mesh
 * @author 		Suleman Chikhalia
 * @copyright 	(c) Suleman Chikhalia
 * @licence 	MIT
 */
abstract class Mesh_Rule_Core {

	const NOT_EMPTY = 'not_empty';
	const LENGTH = 'length';
	const MIN_LENGTH = 'min_length';
	const MAX_LENGTH = 'max_length';
	const EXACT_LENGTH = 'exact_length';
	const RANGE = 'range';
	const MATCHES = 'matches';

	/**
	 * Checks if a field is not empty.
	 *
	 * @return  boolean
	 */
	public static function not_empty($value)
	{
		if (is_object($value) AND $value instanceof ArrayObject)
		{
			// Get the array from the ArrayObject
			$value = $value->getArrayCopy();
		}

		return ($value === '0' OR ! empty($value));
	}

	/**
	 * Checks that a field is within the character length range.
	 *
	 * @param   string   value
	 * @param   integer  minimum length required
	 * @param   integer  maximum length required
	 * @return  boolean
	 */
	public static function length($value, $min_length, $max_length)
	{
		return (bool) ((Mesh_Rule::min_length($value, $min_length)) && (Mesh_Rule::max_length($value, $max_length)));
	}

	/**
	 * Checks that a field is long enough.
	 *
	 * @param   string   value
	 * @param   integer  minimum length required
	 * @return  boolean
	 */
	public static function min_length($value, $length)
	{
		return UTF8::strlen($value) >= $length;
	}

	/**
	 * Checks that a field is short enough.
	 *
	 * @param   string   value
	 * @param   integer  maximum length required
	 * @return  boolean
	 */
	public static function max_length($value, $length)
	{
		return UTF8::strlen($value) <= $length;
	}

	/**
	 * Checks that a field is exactly the right length.
	 *
	 * @param   string   value
	 * @param   integer  exact length required
	 * @return  boolean
	 */
	public static function exact_length($value, $length)
	{
		return UTF8::strlen($value) === $length;
	}

	/**
	 * Tests if a number is within a range.
	 *
	 * @param   string   number to check
	 * @param   integer  minimum value
	 * @param   integer  maximum value
	 * @return  boolean
	 */
	public static function range($number, $min, $max)
	{
		return ($number >= $min AND $number <= $max);
	}

	/**
	 * Checks if two values are the identical.
	 *
	 * @param   string   field value
	 * @param   string   field value to match
	 * @return  boolean
	 */
	public static function matches($value, $field_label, $match)
	{
		return ($value === $match);
	}

} // End Mesh Rule Core