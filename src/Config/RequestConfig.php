<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Config;


class RequestConfig
{
	public function __construct(
		public string $name,
		public string $url,
		public ?array $post = null,
		public ?IdentityConfig $identityConfig = null,
		public ?FormConfig $formConfig = null,
		public ?AssertConfig $assertConfig = null,
		public bool $refresh = false,
		public bool $reset = false,
	) {}


	public static function createFromArray(array $config)
	{
		$request = new self($config['name'], $config['url']);
		if (array_key_exists('post', $config)) {
			$request->post = $config['post'];
		}
		if (array_key_exists('identity', $config)) {
			$request->identityConfig = IdentityConfig::createFromArray($config['identity']);
		}
		if (array_key_exists('form', $config)) {
			$request->formConfig = FormConfig::createFromArray($config['form']);
		}
		if (array_key_exists('asserts', $config)) {
			$request->assertConfig = AssertConfig::createFromArray($config['asserts']);
		}
		if (array_key_exists('refresh', $config)) {
			$request->refresh = $config['refresh'];
		}
		if (array_key_exists('reset', $config)) {
			$request->reset = $config['reset'];
		}
		return $request;
	}
}

