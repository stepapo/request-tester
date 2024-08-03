<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Http\UrlScript;
use Stepapo\RequestTester\RequestTester;
use Stepapo\UrlTester\Mock\HttpRequest;
use Stepapo\UrlTester\Mock\Session;


class RequestTesterExtension extends CompilerExtension
{
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		if ($builder->hasDefinition('http.request')) {
			$builder->getDefinition('http.request')
				->setFactory(HttpRequest::class, [new Statement(UrlScript::class)]);
		}
//		if ($builder->hasDefinition('session.session')) {
//			$builder->getDefinition('session.session')
//				->setFactory(Session::class);
//		}
		$builder->addDefinition($this->prefix('requestTester.tester'))
			->setFactory(RequestTester::class);
	}
}