<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Config;


class Form extends Config
{
	public string $name;
	public ?array $post = null;
}
