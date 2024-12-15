<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\ToArray;
use Stepapo\Utils\Config;


class Identity extends Config
{
	public ?int $id = null;
	#[ToArray] public ?array $roles = null;
	public ?string $username = null;
	public ?string $domain = null;
}
