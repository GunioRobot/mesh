<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mesh validation system.
 * 
 * @package 	Mesh
 * @author 		Suleman Chikhalia
 * @copyright 	(c) Suleman Chikhalia
 * @licence 	MIT
 */
abstract class Mesh_Core extends ArrayObject {
	
	// enable debugging?
	public static $debugging = TRUE;
	
	// fields, name => field
	protected $fields = array();
	
	// field labels
	protected $labels = array();
	
	// error message list
	protected $messages = array();
	
	// error messages file
	protected $message_file;
	
	// validation passed or failed
	protected $passed = FALSE;
	
	// debug information
	protected static $debug = array();
	
	/**
	 * Creates a new Mesh instance.
	 * 
	 * @param 	array 	$data 	data to be validated
	 * @param 	string 	$file	error message file
	 * @return 	Mesh
	 */
	public static function factory(array $data = array(), $file = NULL)
	{
		return new Mesh($data, $file);
	}
	
	/**
	 * Creates an ArrayObject from the passed array and sets the error
	 * messages file.
	 * 
	 * @param 	array 	$data 	data to be validated
	 * @param 	string 	$file	error message file
	 * @return 	void
	 */
	public function __construct(array $data = array(), $file = NULL)
	{
		// set the data to be validated
		parent::__construct($data, ArrayObject::STD_PROP_LIST);
		
		// set the message file
		if(is_string($file))
		{
			$this->message_file = $file;
		}
	}
	
	/**
	 * Getter and setter method for Mesh field objects.
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	Mesh_Field 	$field 	Mesh Field instance
	 * @param 	string 	$label 	field display name
	 * @return 	NULL|Mesh_Field|Mesh 	getter returns a Mesh_Field instance (or NULL) and setter returns a Mesh instance
	 */
	public function field($name, Mesh_Field $field = NULL, $label = NULL)
	{
		// getter; return the Mesh field object by name
		if($field === NULL)
		{
			$value = $this->fields[$name];
		}
		// setter; set the field instance and label
		else
		{
			$this->fields[$name] = $field;
			
			// set the field label
			if($label === NULL)
			{
				$this->label($name, $name);
			}
			else
			{
				$this->label($name, $label);
			}
			
			$value = $this;
		}
		
		return $value;
	}
	
	/**
	 * Getter and setter for the field display name.
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	string 	$label 	[optional] label name
	 * @return 	string|Mesh 	getter returns a string and setter returns a Mesh instance
	 */
	public function label($name, $label = NULL)
	{
		// getter; return the display name
		if($label === NULL)
		{
			$value = $this->labels[$name];
		}
		// setter; set display name
		else
		{
			$this->labels[$name] = $label;
			
			$value = $this;
		}
		
		return $value;
	}
	
	/**
	 * Getter and setter for a field value.
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	string 	$value 	[optional] value to validate
	 * @return 	string|Mesh 	getter returns a string and setter returns a Mesh instance
	 */
	public function value($name, $value = NULL)
	{
		$return_value = '';
		
		// get field by name
		$field = $this->field($name);
		
		// getter
		if($value === NULL)
		{
			// if the field is found
			if($field instanceof Mesh_Field)
			{
				if($this->passed)
				{
					// return the clean value (if available)
					$return_value = $field->value();
				}
			}
			
			// return the dirty (pre-validation) value if the 
			// field is not found or if validation has falied
			if(empty($return_value))
			{
				$return_value = $this[$name];
			}
		}
		// setter
		else
		{
			// set value in Mesh
			$this[$name] = $value;
			
			// if the field is found
			if($field instanceof Mesh_Field)
			{
				// set the field value
				$field->value($value);
			}
			
			$return_value = $this;
		}
		
		return $return_value;
	}
	
	/**
	 * Return the field value otherwise return the default value
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	string 	$default 	default value
	 * @return 	string 	form value otherwise the default value
	 */
	public function value_default($name, $default)
	{
		// get field value
		$value = $this->value($name);
		
		// return the default value if no return value has been set
		if($value === NULL)
		{
			$value = $default;
		}
		
		return $value;
	}
	
	/**
	 * Combined getter and setter. Getter returns the array representation of the 
	 * current object.
	 * 
	 * @param 	array 	$value 	[optional] values to validate
	 * @param 	string 	$mode 	[optional] mode; 'APPEND' or 'EXCHANGE' values
	 * @return 	array|Mesh 	getter returns an array and setter returns a Mesh instance
	 */
	public function values(array $values = array(), $mode = 'APPEND')
	{
		// getter; return the values to validate
		if($values === array())
		{
			// if validation has passed return clean values
			if($this->passed)
			{
				// fetch value for each field
				foreach($this->fields as $field_name => $field)
				{
					$field_value = $field->value();
					
					// only replace the original value if the field has a value
					if($field_value !== NULL)
					{
						$value[$field_name] = $field_value;
					}
				}
				
				try
				{
					// return cleaned and validated values and other values 
					// that were set but not validated
					$value = array_merge($this->getArrayCopy(), $value);
				}
				catch(Exception $e)
				{
					$value = $this->getArrayCopy();
				}
			}
			// return original values
			else
			{
				$value = $this->getArrayCopy();
			}
		}
		// setter; set the values to validate
		else
		{
			if($mode === 'APPEND')
			{
				// append values
				$this->exchangeArray(array_merge($this->getArrayCopy(), $values));
			}
			else
			{
				// exchange values
				$this->exchangeArray($values);
			}
			
			$value = $this;
		}
		
		return $value;
	}
	
	/**
	 * Get the last error for a field.
	 * 
	 * @param 	string 	field name
	 * @return 	string|bool 	returns function call name or FALSE
	 */
	public function error($name)
	{
		$value = FALSE;
		
		// get errors for a field
		$errors = $this->errors($name);
		
		if((is_array($errors)) && ($errors !== array()))
		{
			$error = end($errors);
			
			if(is_array($error))
			{
				$value = current($error);
			}
		}
		
		return $value;
	}
	
	/**
	 * Get errors for a field.
	 * 
	 * @param 	string 	field name
	 * @return 	array|bool 	returns an array of errors otherwise FALSE 
	 */
	public function errors($name)
	{
		// get field
		$field = $this->field($name);
		
		if($field instanceof Mesh_Field)
		{
			$value = $field->errors();
		}
		else
		{
			$value = FALSE;
		}
		
		return $value;
	}
	
	/**
	 * Get the error message by field name.
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	string 	$file 	[optional] messages file
	 * @param 	bool 	$translate 	[optional] translate the message
	 * @return 	string 	error message
	 */
	public function message($name, $file = NULL, $translate = TRUE)
	{
		$errors_count = 0;
		
		// get message
		$message = $this->messages[$name];
		
		// return already generated message
		if((is_string($message)) && ($file === NULL) && ($translate === TRUE))
		{
			$value = $message;
		}
		
		$errors = $this->errors($name);
		
		if($errors !== FALSE)
		{
			$errors_count = count($errors);
		}
		
		// generate message for each failed rule
		for($i = 0; $i < $errors_count; $i++)
		{
			$message = $this->fetch_message($name, $errors[$i], $this->message_file, $translate);
			
			// Set the message for this field
			$this->messages[$name] = $message;
		}
		
		return $message;
	}
	
	/**
	 * Getter and setter for messages.
	 * 
	 * @param 	array|string 	$file 	set messages if array is supplied, [optional] string is messages file
	 * @param 	bool 	$translate 	[optional] translate the message
	 * @return 	array|Mesh 	getter returns an array of messages and setter returns a Mesh instance
	 */
	public function messages($file = NULL, $translate = TRUE)
	{
		if(is_array($file))
		{
			$this->messages = $file;
			
			$value = $this;
		}
		else
		{
			// no message file set
			if(($file === NULL) && ($this->message_file === NULL))
			{
				// Return the error message list
				$value = $this->messages;
			}
			elseif(is_string($file))
			{
				$this->message_file = $file;
			}
			
			// get errors for each field
			foreach($this->fields as $name => $field)
			{
				$this->message($name);
			}
			
			$value = $this->messages;
		}
		
		return $value;
	}
	
	/**
	 * Validate the Mesh.
	 * 
	 * @return 	bool 	validation passed or failed
	 */
	public function check()
	{
		$passed = TRUE;
		
		// check each field
		foreach($this->fields as $name => $field)
		{
			$value = $this[$name];
			
			if(!$field->check($value))
			{
				// field validation failed
				$passed = FALSE;
			}
			
			// append field debug information
			self::$debug[$name] = Mesh_Field::debug();
		}
		
		$this->passed = $passed;
		
		return $passed;
	}
	
	/**
	 * Returns the result of the validation check.
	 * 
	 * @return 	bool 	validation passed or failed
	 */
	public function passed()
	{
		return $this->passed;
	}
	
	/**
	 * Compare values for use in select drop downs. 
	 * 
	 *      <select name="gender">
	 *          <option value="1"<?php echo $mesh->selected('day', '1'); ?>>Male</option>
	 *          <option value="2"<?php echo $mesh->selected('day', '2'); ?>>Female</option>
	 *      </select>
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	string 	$value 	field value
	 * @param 	string 	$default 	[optional] default value
	 * @return 	string 	returns ' selected="selected"' a match is found
	 */
	public function selected($name, $value, $default = NULL)
	{
		$return_value = '';
		
		$field_value = $this->value($name);
		
		if(($field_value === $value) || ($default === $value))
		{
			$return_value = ' selected="selected"';
		}
		
		return $return_value;
	}
	
	/**
	 * Return debug information.
	 * 
	 * @return 	array 	debug information
	 */
	public static function debug()
	{
		return self::$debug;
	}
	
	/**
	 * Fetch and translate error message.
	 * 
	 * @param 	string 	$name 	field name
	 * @param 	array 	$error 	error array
	 * @param 	string 	$file 	messages file
	 * @param 	bool 	$translate 	[optional] translate the message
	 * @return 	string 	error message
	 */
	protected function fetch_message($name, array $error, $file, $translate = TRUE)
	{
		list($error, $params) = $error;
		
		if($message = Kohana::message($file, "{$name}.{$error}"))
		{
			// Found a message for this field and error
		}
		elseif($message = Kohana::message($file, "{$name}.default"))
		{
			// Found a default message for this field
		}
		elseif($message = Kohana::message('rule', $error))
		{
			// Found a rule message for this error
		}
		elseif($message = Kohana::message('format', $error))
		{
			// Found a format type message for this error
		}
		elseif($message = Kohana::message('validate', $error))
		{
			// Found a default message for this error
		}
		else
		{
			// No message exists, display the path expected
			$message = "{$file}.{$name}.{$error}";
		}
		
		if($translate == TRUE)
		{
			$label = $this->label($name);
			
			// Start the translation values list
			$values = array(':field' => $this->label($name));
	
			if(!empty($params))
			{
				foreach($params as $key => $value)
				{
					// Add each parameter as a numbered value, starting from 1
					$values[':param' . ($key + 1)] = $value;
				}
			}
			
			if(is_string($translate))
			{
				// Translate the message using specified language
				$message = __($message, $values, $translate);
			}
			else
			{
				// Translate the message using the default language
				$message = __($message, $values);
			}
		}
		
		return $message;
	}
	
} // End Mesh Core