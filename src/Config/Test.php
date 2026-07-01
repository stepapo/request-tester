<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\ArrayOfType;
use Stepapo\Utils\Config;


final class Test extends Config
{
	public string $name;
	/** @var Request[] */ #[ArrayOfType(Request::class)] public RequestList|array $requests;


	public static function createFromArray(mixed $config = [], mixed $key = null, bool $skipDefaults = false, mixed $parentKey = null): static
	{
		$data = new Test;
		$data->name = $config['name'];
		$data->requests = RequestList::createFromArray($config);
		return $data;
	}
}
