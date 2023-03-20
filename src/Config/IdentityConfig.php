<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Config;


class IdentityConfig
{
	public function __construct(
		public ?int $id = null,
		public ?array $roles = null,
		public ?string $username = null,
		public ?string $domain = null
	) {}


	public static function createFromArray(?array $config = null)
	{
		return new self(
			$config['id'] ?? null,
			isset($config['roles']) ? (array) $config['roles'] : null,
			$config['username'] ?? null,
			$config['domain'] ?? null,
		);
	}
}
