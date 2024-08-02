<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Schematic;


class FormConfig extends Schematic
{
	public string $name;
	public ?array $post = null;
}
