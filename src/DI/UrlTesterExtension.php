<?php declare(strict_types=1);

namespace Stepapo\UrlTester\DI;

use Stepapo\UrlTester\Helper;
use Stepapo\UrlTester\Mock\HttpRequest;
use Stepapo\UrlTester\Mock\Session;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Http\UrlScript;
use Stepapo\UrlTester\Mock\User;
use Stepapo\UrlTester\UrlTester;


class UrlTesterExtension extends CompilerExtension
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

		$builder->addDefinition($this->prefix('urlTester.tester'))
			->setFactory(UrlTester::class, ['baseUrl' => $config['baseUrl']]);
	}
}
