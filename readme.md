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
<?php

$container = Bootstrap::bootForTests()->createContainer();

/**
 * @testCase
 * @dataProvider config.php
 */
class PresenterTest extends Stepapo\UrlTester\Tester\TestCase
{
}

$container->createInstance(PresenterTest::class, [Nette\Tester\Environment::loadData()])->run();
```

## Configuration

NEON files are used to configure test scenarios. They can be separated in following parts.

### Test

Test is defined by name and list of requests.

```neon
name: authorization
requests:
  example request: # include Request configuration    
  another example request: # include Request configuration
```

### Request

Request configuration requires a URL and asserts. Request can have subrequests that inherit parent request configuration.

```neon
url: auth
identity: # include Identity configuration if you want a logged user to be specified
form: # include Form configuration if you want to submit a form on the url
asserts: # include Assert configuration
requests:
  example subrequest: # include Request configuration    
  another example subrequest: # include Request configuration
```

### Identity

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
