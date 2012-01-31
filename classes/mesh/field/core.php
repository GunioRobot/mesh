<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mesh Field.
 *
 * @package 	Mesh
 * @author 		Suleman Chikhalia
 * @copyright 	(c) Suleman Chikhalia
 * @licence 	MIT
 */
abstract class Mesh_Field_Core {

	// field name
	protected $name;

	// field display name
	protected $label;

	// value to clean and validate
	protected $value_dirty;

	// filtered and cleaned value
	protected $value_clean;

	// field is required?
	protected $required = FALSE;

	// queue of filters, rules and callbacks
	protected $queue = array();

	// error list, field => rule
	protected $errors = array();

	// messages list, field => message
	protected $messages = array();

	// debug information
	protected static $debug = array();

	/**
	 * Create a new Mesh Field instance.
	 *
	 * @param 	string 		field name
	 * @return 	Mesh_Field
	 */
	public static function factory($name, $label = NULL)
	{
		return new Mesh_Field($name);
	}

	/**
	 * Sets the field name and label.
	 *
	 * @param 	string 	$name 	field name
	 * @param 	string 	$label 	[optional] field display name
	 * @return 	void
	 */
	public function __construct($name, $label = NULL)
	{
		// set the field name
		$this->name = $name;

		// set the field display name
		$this->label = $label;
	}

	/**
	 * Getter and setter for the field name.
	 *
	 * @param 	string 	$name 	[optional] field name value
	 * @return 	string|Mesh_Field 	getter returns a string and setter returns a Mesh_Field
	 */
	public function name($name = NULL)
	{
		return $this->getter_setter('name', $name);
	}

	/**
	 * Sets the field display name.
	 *
	 * @param 	string 	$label 	[optional] label value
	 * @return 	string|Mesh_Field 	getter returns a string and setter returns a Mesh_Field
	 */
	public function label($label = NULL)
	{
		$value = $this->getter_setter('label', $label);

		if($value === NULL)
		{
			$value = $this->name;
		}

		return $value;
	}

	/**
	 * Add a new filter. Each filter will be executed once.
	 *
	 *     // Run trim() on the value
	 *     $field->filter('trim');
	 *
	 * @param 	mixed 	$filter 	valid PHP callback
	 * @param 	array 	$params 	[optional] additional paramaters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function filter($filter, array $params = array())
	{
		return $this->add('filter', $filter, $params);
	}

	/**
	 * Add filters using an array.
	 *
	 * @param 	array 	$filters 	array of filters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function filters(array $filters)
	{
		return $this->add_array('filter', $filters);
	}

	/**
	 * Add a new format rule. Each rule will be executed once.
	 * All format rules must be string names of functions.
	 *
	 *     // email address must be a valid
	 *     $field->format(Mesh_Format::EMAIL)
	 *
	 * @param 	string 	$format 	function or static method name
	 * @param 	array 	$params 	[optional] additional parameters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function format($format, array $params = array())
	{
		return $this->add('format', $format, $params);
	}

	/**
	 * Add a new rule. Each rule will be executed once.
	 * All rules must be string names of functions.
	 *
	 *     // username must not be empty, have a minimum length of
	 *     // 4 characters and must be unique
	 *     $field->rule(Mesh_Rule::NOT_EMPTY)
	 *           ->rule(Mesh_Rule::MIN_LENGTH, array(4))
	 *           ->rule('Model_User::username_unique');
	 *
	 * @param 	string 	$rule 	function or static method name
	 * @param 	array 	$params 	[optional]additional parameters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function rule($rule, array $params = array())
	{
		return $this->add('rule', $rule, $params);
	}

	/**
	 * Add rules using an array.
	 *
	 * @param 	array 	$rules 	array of rules
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function rules(array $rules)
	{
		return $this->add_array('rule', $rules);
	}

	/**
	 * Add a new callback. Each callback will be executed only once.
	 *
	 *     // log all search queries
	 *     $field->callback(array($this, 'log_search_query'));
	 *
	 * Alternatively you could pass a string defining a static method call
	 *
	 *      // log all search queries
	 *      $field->callback('Logger::search_query');
	 *
	 * @param 	mixed 	$callback 	valid PHP callback or string for static method call
	 * @param 	array 	$params 	[optional] additional parameters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function callback($callback, array $params = array())
	{
		return $this->add('callback', $callback, $params);
	}

	/**
	 * Add callbacks using an array.
	 *
	 * @param 	array 	$callbacks 	array of callbacks
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function callbacks(array $callbacks)
	{
		return $this->add_array('callback', $callbacks);
	}

	/**
	 * Add an error.
	 *
	 * @param 	string 	$error 	error
	 * @param 	array 	$params 	[optional] additional parameters
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function error($error, array $params = NULL)
	{
		$this->errors[] = array($error, $params);

		return $this;
	}

	/**
	 * Return all errors.
	 *
	 * @return 	array 	errors
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Getter and setter for the field value. Getter returns the cleaned
	 * value if this field validates otherwise returns the original value.
	 * Setter sets the dirty value.
	 *
	 * @param 	string 	$value 	[optional] field value
	 * @return 	mixed|Mesh_Field 	getter returns a value of any type and setter returns a Mesh_Field
	 */
	public function value($value = NULL)
	{
		// getter; return the field value
		if($value === NULL)
		{
			// check if the cleaned value is available
			if($this->value_clean !== NULL)
			{
				$return_value = $this->value_clean;
			}
			else
			{
				$return_value = $this->value_dirty;
			}
		}
		// setter; set the field value
		else
		{
			// set (clean and dirty) value
			$this->value_clean = $this->value_dirty = $value;

			$return_value = $this;
		}

		return $return_value;
	}

	/**
	 * Return the clean value
	 *
	 * @return 	mixed 	cleaned field value
	 */
	public function value_clean()
	{
		return $this->value_clean;
	}

	/**
	 * Return the original value.
	 *
	 * @return 	mixed 	original field value
	 */
	public function value_dirty()
	{
		return $this->value_dirty;
	}

	/**
	 * Exclude a filter/format/rule/callback from being processed.
	 *
	 * @param 	mixed 	$call 	name of function/callback
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	public function exclude($call)
	{
		// set this field as not required
		if($call === 'not_empty')
		{
			$this->required = FALSE;
		}

		// find and exclude function call
		for($i = 0; $i < count($this->queue); $i++)
		{
			if($this->queue[$i]['call'] === $call)
			{
				$this->queue[$i]['exclude'] = TRUE;
			}
		}

		return $this;
	}

	/**
	 * Validate the field.
	 *
	 * @param 	string 	$value 	[optional] value to validate
	 * @return 	bool 	pass or fail
	 */
	public function check($value = NULL)
	{
		if(Kohana::$profiling === TRUE)
		{
			// Start a new benchmark
			$benchmark = Profiler::start('Mesh', __FUNCTION__);
		}

		// use pre-defined value if not supplied
		if($value === NULL)
		{
			$value = $this->value_dirty;
		}

		$queue = $this->queue;
		$queue_count = count($queue);

		// clear any debug informtion
		self::$debug = array();

		for($i = 0; $i < $queue_count; $i++)
		{
			$type = $queue[$i]['type'];
			$call = $queue[$i]['call'];
			$params = $queue[$i]['params'];
			$exclude = $queue[$i]['exclude'];

			$debug = array(
				'for'      => $this->name,
				'type'     => $type,
				'function' => $call,
				'value'    => $value,
				'passed'   => FALSE,
				'exclude'  => FALSE,
			);

			// skip excluded rule or if the value is NULL (and the field isn't required)
			if(($exclude) || (($value === NULL) && (!$this->required)))
			{
				$debug['exclude'] = TRUE;

				// add debug information
				self::$debug[] = $debug;

				continue;
			}

			switch($type) {
				case 'filter':
					$value = $this->process_filter($value, $call, $params);
					break;
				case 'format':
					$passed = $this->process_format($value, $call, $params);

					// validate format
					if($passed === FALSE)
					{
						// add error
						$this->error($call, $params);
					}

					break;
				case 'rule':
					$passed = $this->process_rule($value, $call, $params);

					// validate rule
					if($passed === FALSE)
					{
						// add error
						$this->error($call, $params);
					}

					break;
				case 'callback':
					$this->process_callback($value, $call, $params);
					break;
			}

			$debug['passed'] = $passed;

			// add debug information
			self::$debug[] = $debug;

			// new error may be added, stop processing
			if($this->errors() !== array())
			{
				break;
			}
		}

		// set filtered value if there are no errors
		if($this->errors() === array())
		{
			$this->value_clean = $value;
		}

		if(isset($benchmark))
		{
			// Stop benchmarking
			Profiler::stop($benchmark);
		}

		return empty($this->errors);
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
	 * Generic add filter/format/rule/callback to queue.
	 *
	 * @param 	string 	$type 	'filter', 'format', 'rule' or 'callback'
	 * @param 	mixed 	$function 	valid PHP callback or name of function
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	protected function add($type, $function, array $params = array())
	{
		// add to queue
		$this->queue[] = array(
			'type' => $type,
			'call' => $function,
			'params' => $params,
			'exclude' => FALSE,
		);

		// set this field as required
		if(($type === 'rule') && ($function === 'not_empty'))
		{
			$this->required = TRUE;
		}

		return $this;
	}

	/**
	 * Generic add using an array.
	 *
	 * @param 	string 	$type 	'filter', 'rule' or 'callback'
	 * @param 	array 	$functions 	an array of filters, rules or callbacks
	 * @return 	Mesh_Field 	current Mesh_Field instance
	 */
	protected function add_array($type, array $functions)
	{
		foreach($functions as $function => $params)
		{
			$this->$type($function, $params);
		}

		return $this;
	}

	/**
	 * Process a filter call.
	 *
	 * @param 	string 	$value 	value to validate
	 * @param 	mixed 	$filter 	function call
	 * @return 	string 	cleaned value
	 */
	protected function process_filter($value, $filter, $params = array())
	{
		// Add the field value to the parameters
		array_unshift($params, $value);

		if(strpos($filter, '::') === FALSE)
		{
			// Use a function call
			$function = new ReflectionFunction($filter);

			// Call $function($this[$field], $param, ...) with Reflection
			$value = $function->invokeArgs($params);
		}
		else
		{
			// Split the class and method of the rule
			list($class, $method) = explode('::', $filter, 2);

			// Use a static method call
			$method = new ReflectionMethod($class, $method);

			// Call $Class::$method($this[$field], $param, ...) with Reflection
			$value = $method->invokeArgs(NULL, $params);
		}

		return $value;
	}

	/**
	 * Process a format call.
	 *
	 * @param 	string 	$value 	value to validate
	 * @param 	mixed 	$format 	function call
	 * @return 	bool 	pass or fail
	 */
	protected function process_format($value, $format, $params = array())
	{
		$mesh_format = new Mesh_Format();

		// Add the field value to the parameters
		array_unshift($params, $value);

		if(method_exists($mesh_format, $format))
		{
			// Use a method in this object
			$method = new ReflectionMethod($mesh_format, $format);

			if($method->isStatic())
			{
				// Call static::$rule($this[$field], $param, ...) with Reflection
				$passed = $method->invokeArgs(NULL, $params);
			}
			else
			{
				// Do not use Reflection here, the method may be protected
				$passed = call_user_func_array(array($mesh_format, $format), $params);
			}
		}
		else
		{
			// Split the class and method of the format rule
			list($class, $method) = explode('::', $format, 2);

			// Use a static method call
			$method = new ReflectionMethod($class, $method);

			// Call $Class::$method($this[$field], $param, ...) with Reflection
			$passed = $method->invokeArgs(NULL, $params);
		}

		return $passed;
	}

	/**
	 * Process a rule call.
	 *
	 * @param 	string 	$value 	value to validate
	 * @param 	mixed 	$rule 	function call
	 * @return 	bool 	pass or fail
	 */
	protected function process_rule($value, $rule, $params = array())
	{
		$mesh_rule = new Mesh_Rule();

		// Add the field value to the parameters
		array_unshift($params, $value);

		if(method_exists($mesh_rule, $rule))
		{
			// Use a method in this object
			$method = new ReflectionMethod($mesh_rule, $rule);

			if($method->isStatic())
			{
				// Call static::$rule($this[$field], $param, ...) with Reflection
				$passed = $method->invokeArgs(NULL, $params);
			}
			else
			{
				// Do not use Reflection here, the method may be protected
				$passed = call_user_func_array(array($mesh_rule, $rule), $params);
			}
		}
		elseif(strpos($rule, '::') === FALSE)
		{
			// Use a function call
			$function = new ReflectionFunction($rule);

			// Call $function($this[$field], $param, ...) with Reflection
			$passed = $function->invokeArgs($params);
		}
		else
		{
			// Split the class and method of the rule
			list($class, $method) = explode('::', $rule, 2);

			// Use a static method call
			$method = new ReflectionMethod($class, $method);

			// Call $Class::$method($this[$field], $param, ...) with Reflection
			$passed = $method->invokeArgs(NULL, $params);
		}

		return $passed;
	}

	/**
	 * Process a callback.
	 *
	 * @param 	string 	$value 	value to validate
	 * @param 	mixed 	$callback 	function call
	 * @return 	void
	 */
	protected function process_callback($value, $callback, $params = array())
	{
		if((is_string($callback)) && (strpos($callback, '::') !== FALSE))
		{
			// Make the static callback into an array
			$callback = explode('::', $callback, 2);
		}

		if(is_array($callback))
		{
			// Separate the object and method
			list ($object, $method) = $callback;

			// Use a method in the given object
			$method = new ReflectionMethod($object, $method);

			if(!is_object($object))
			{
				// The object must be NULL for static calls
				$object = NULL;
			}

			// Call $object->$method($this, $field, $errors) with Reflection
			$method->invoke($object, $this, $params);
		}
		else
		{
			// Use a function call
			$function = new ReflectionFunction($callback);

			// Call $function($this, $field, $errors) with Reflection
			$function->invoke($this, $field);
		}
	}

	/**
	 * Generic getter and setter method for class members.
	 *
	 * @param 	string 	$member 	class member name
	 * @param 	mixed 	$value 	optional value to be set
	 * @return  mixed|Mesh_Field 	getter returns a value of any type and setter returns a Mesh_Field
	 */
	private function getter_setter($member, $value = NULL)
	{
		// getter; return class member value
		if($value === NULL)
		{
			$return_value = $this->$member;
		}
		// setter; set class member and return $this
		else
		{
			// set class member value
			$this->$member = $value;

			$return_value = $this;
		}

		return $return_value;
	}

} // End Mesh Field Core