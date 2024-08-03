<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\ToArray;
use Stepapo\Utils\Schematic;


class AssertConfig extends Schematic
{
	public ?int $httpCode = null;
	#[ToArray] public ?array $renders = null;
	#[ToArray] public ?array $notRenders = null;
	public ?array $json = null;
}
