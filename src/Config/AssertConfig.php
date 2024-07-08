<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Config;


class AssertConfig
{
	public function __construct(
		public ?int $httpCode = null,
		public ?array $renders = null,
		public ?array $notRenders = null,
		public ?array $json = null,
	) {}


	public static function createFromArray(array $config)
	{
		return new self(
			$config['httpCode'] ?? null,
			isset($config['renders']) ? (array) $config['renders'] : null,
			isset($config['notRenders']) ? (array) $config['notRenders'] : null,
			$config['json'] ?? null
		);
	}
}
