Mesh is a Validation module for Kohana.

## Features

* **Easily Extensible** — Need to add your own formats or rules? Simply extend the 
  `Mesh_Format` or `Mesh_Rule` classes and add your own defined formats and rules.

* **Process Queue** — Run through filters, rules, formats and callbacks in any order. 
  Functions are processed in the order they are added to the Mesh.

* **Exclude functions from validation** — Reuse a Mesh validation set and exclude the 
  functions that you don't want processed instead of having to redefine the set.

## Usage

### Define Mesh Fields

	// define a mesh field for the users email address
	$email_address_field = Mesh_Field::factory('email_address')
	    ->filter('trim')
	    ->rule(Mesh_Rule::NOT_EMPTY)
	    ->format(Mesh_Format::EMAIL)
	    ->rule('Model_User::email_address_unique');
	
	// define a mesh field for the users password
	$password_field = Mesh_Field::factory('password')
	    ->filter('trim')
	    ->rule(Mesh_Rule::NOT_EMPTY)
	    ->rule(Mesh_Rule::MIN_LENGTH, array(4))
	    ->rule(Mesh_Rule::MAX_LENGTH, array(20))

### Create a Mesh

	// error messages file; {application}/messages/login.php
	$error_messages_file = 'account';
	
	// create a Mesh
	$form = Mesh::factory($_POST, 'login')
	        ->field('email_address', $email_address_field, 'Email address')
			->field('password', $password_field, 'Password');

### Validate a Mesh

	if($form->check())
	{
		// form validates, Yay!
	}
	else
	{
		// boo, we have some errors
		$error_messages = $form->messages();
	}
	
## Debug

    <?php echo View::factory('mesh/debug'); ?>
