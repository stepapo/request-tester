# URL Tester

URL Tester is an extension for Nette Framework used for testing web applications. It validates rendered content based on what URL is requested, which user is logged in or what data was submitted through a form. The docs show basic example and explains ways of configurating tests.

## Example

Let's define a test for submiting an authorization form on url `//example.com/auth`.

### Definition

Use `tests/config/authorization.neon` to define a test:

```neon
name: authorization
url: auth
requests:
	with wrong name:
		form:
			name: signupForm
			post:
				login: WRONG_NAME
				password: secret1234
		asserts:
			renders: This login does not exist
	with wrong passowrd:  
		form:
			name: signupForm
			post:
				login: name@example.com
				password: WRONG_PASSWORD
		asserts:
			renders: The password is wrong
	with wrong address:
		url: auht
		asserts:
			httpCode: 404
	with correct input:
		form:
			name: signupForm
			post:
				login: name@example.com
				password: secret1234
		asserts:
			renders: Login successful
```

### Data provider

Use `tests/config.php` to collect all test definitions and include them in data provider for Nette Tester:

```php
require __DIR__ . '/../vendor/autoload.php';

$return = [];

foreach (Nette\Utils\Finder::findFiles('*.neon')->from(__DIR__ . '/config') as $file) {
	$config = (array) Nette\Neon\Neon::decode(Nette\Utils\FileSystem::read($file));
	$return[$config['name']] = $config;
}

return $return;

```

### Test case

Create `tests/PresenterTest.php` test case:

```php
$container = App\Bootstrap::bootForTests()->createContainer();

/**
 * @testCase
 * @dataProvider config.php
 */
class PresenterTest extends Stepapo\UrlTester\Tester\TestCase
{
}

$container->createInstance(PresenterTest::class, [Nette\Tester\Environment::loadData()])->run();
```

### Runner setup

Specific URL Tester printer can be used for outputing results instead of basic Nette Tester printer. To do that, create `tests/runner-setup.php`:

```php
require __DIR__ . '/../vendor/autoload.php';

$runner->outputHandlers[] = new Stepapo\UrlTester\Tester\UrlPrinter(
	$runner,
	require __DIR__ . '/config.php',
);
```

### Run

To run tests use standard Nette Tester command. Make sure testing database is prepared and temp folder is cleared.

Basic command:

	$ tester tests

Command with setup:

	$ tester --setup tests/runner-setup.php -c tests/php.ini --coverage tests/coverage.html --coverage-src app -j 8 --cider tests

## Configuration

NEON files are used to configure test scenarios. They can be separated in following parts.

### Test

Test is defined by `name` and list of `requests`.

```neon
name: authorization
requests:
	example request: # include Request configuration    
	another example request: # include Request configuration
```

### Request

Request configuration requires `url` and `asserts`. Use `identity` to specify which user should be logged. Use `form` if you want to submit a form. `requests` can be used to specify subrequests that inherit parent request configuration and override some of it with their own if needed.

```neon
url: auth
identity: # include Identity configuration
form: # include Form configuration
asserts: # include Assert configuration
requests:
	example subrequest: # include Request configuration    
	another example subrequest: # include Request configuration
```

### Identity

`id` of logged user is required.

```neon
id: 1
roles:
	- user
	- admin
```

### Form

```neon
name: signupForm
post:
	login: name@domain.com
	password: secret1234
```

### Assert

Validating bad request:

```neon
httpCode: 404
```

Validating what is rendered in browser or not:

```neon
renders:
	- Login successful
notRenders:
	- Login required
```

Validating result of API call:

```neon
json:
	id: 1
	name: John Doe
```
