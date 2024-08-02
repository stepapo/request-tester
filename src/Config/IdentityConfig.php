<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\ToArray;
use Stepapo\Utils\Schematic;


class IdentityConfig extends Schematic
{
	public ?int $id = null;
	#[ToArray] public ?array $roles = null;
	public ?string $username = null;
	public ?string $domain = null;
}
