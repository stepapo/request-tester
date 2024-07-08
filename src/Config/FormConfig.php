<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Config;


class FormConfig
{
	public function __construct(
		public string $name,
		public ?array $post = null
	) {}


	public static function createFromArray(?array $config = null)
	{
		return new self(
			$config['name'],
			$config['post'] ?? null,
		);
	}
}
