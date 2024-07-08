<?php declare(strict_types=1);

namespace Stepapo\RequestTester\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Http\UrlScript;
use Stepapo\RequestTester\Helper;
use Stepapo\RequestTester\Mock\HttpRequest;
use Stepapo\RequestTester\Mock\Session;
use Stepapo\RequestTester\Mock\User;
use Stepapo\RequestTester\RequestTester;


class RequestTesterExtension extends CompilerExtension
{
	public $defaults = [
		'baseUrl' => 'https://test.dev',
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		if ($builder->hasDefinition('http.request')) {
			$builder->getDefinition('http.request')
				->setFactory(HttpRequest::class, [new Statement(UrlScript::class, [$config['baseUrl']])]);
		}

		if ($builder->hasDefinition('session.session')) {
			$builder->getDefinition('session.session')
				->setFactory(Session::class);
		}

		if ($builder->hasDefinition('security.user')) {
			$builder->getDefinition('security.user')
				->setFactory(User::class);
		}

		$builder->addDefinition($this->prefix('helper'))
			->setFactory(Helper::class);

		$builder->addDefinition($this->prefix('requestTester.tester'))
			->setFactory(RequestTester::class, ['baseUrl' => $config['baseUrl']]);
	}
}
