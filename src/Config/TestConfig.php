<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Config;

use Nette\Neon\Neon;
use Nette\Utils\FileSystem;


class TestConfig
{
	/** @param RequestConfig[] $requests */
	public function __construct(
		public string $name,
		public array $requests
	) {}


	public static function createFromNeon(string $file): TestConfig
	{
		return self::createFromArray((array) Neon::decode(FileSystem::read($file)));
	}


	public static function createFromArray(array $config): TestConfig
	{
		return new self(
			$config['name'],
			RequestConfigList::createFromArray($config)
		);
	}
}
