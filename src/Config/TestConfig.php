<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\ArrayOfType;
use Stepapo\Utils\Schematic;


class TestConfig extends Schematic
{
	public string $name;
	/** @var RequestConfig[] */ #[ArrayOfType(RequestConfig::class)] public RequestConfigList|array $requests;


	public static function createFromArray(mixed $config = [], mixed $key = null, bool $skipDefaults = false): static
	{
		$data = new self;
		$data->name = $config['name'];
		$data->requests = RequestConfigList::createFromArray($config);
		return $data;
	}
}
