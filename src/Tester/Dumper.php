<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Tester;

use Tester\Assert;
use Tester\AssertException;
use Tester\Helpers;


class Dumper extends \Tester\Dumper
{
	public static function dumpException(\Throwable $e): string
	{
		$trace = $e->getTrace();
		array_splice($trace, 0, $e instanceof \ErrorException ? 1 : 0, [['file' => $e->getFile(), 'line' => $e->getLine()]]);

		$testFile = null;
		foreach (array_reverse($trace) as $item) {
			if (isset($item['file'])) { // in case of shutdown handler, we want to skip inner-code blocks and debugging calls
				$testFile = $item['file'];
				break;
			}
		}

		if ($e instanceof AssertException) {
			$expected = $e->expected;
			$actual = $e->actual;

			if (is_object($expected) || is_array($expected) || (is_string($expected) && strlen($expected) > self::$maxLength)
				|| is_object($actual) || is_array($actual) || (is_string($actual) && (strlen($actual) > self::$maxLength || preg_match('#[\x00-\x1F]#', $actual)))
			) {
				$args = isset($_SERVER['argv'][1])
					? '.[' . implode(' ', preg_replace(['#^-*([^|]+).*#i', '#[^=a-z0-9. -]+#i'], ['$1', '-'], array_slice($_SERVER['argv'], 1))) . ']'
					: '';
				$stored[] = parent::saveOutput($testFile, $expected, $args . '.expected');
				$stored[] = parent::saveOutput($testFile, $actual, $args . '.actual');
			}

			if ((is_string($actual) && is_string($expected))) {
				for ($i = 0; $i < strlen($actual) && isset($expected[$i]) && $actual[$i] === $expected[$i]; $i++);
				for (; $i && $i < strlen($actual) && $actual[$i - 1] >= "\x80" && $actual[$i] >= "\x80" && $actual[$i] < "\xC0"; $i--);
				$i = max(0, min(
					$i - (int) (self::$maxLength / 3), // try to display 1/3 of shorter string
					max(strlen($actual), strlen($expected)) - self::$maxLength + 3 // 3 = length of ...
				));
				if ($i) {
					$expected = substr_replace($expected, '...', 0, $i);
					$actual = substr_replace($actual, '...', 0, $i);
				}
			}

			$message = $e->origMessage;
			if (((is_string($actual) && is_string($expected)) || (is_array($actual) && is_array($expected)))
				&& preg_match('#^(.*)(%\d)(.*)(%\d.*)$#Ds', $message, $m)
			) {
				$message = ($delta = strlen($m[1]) - strlen($m[3])) >= 3
					? "$m[1]$m[2]\n" . str_repeat(' ', $delta - 3) . "...$m[3]$m[4]"
					: "$m[1]$m[2]$m[3]\n" . str_repeat(' ', strlen($m[1]) - 4) . "... $m[4]";
			}
			$message = strtr($message, [
				'%1' => self::color('yellow') . self::toLine($actual) . self::color('white'),
				'%2' => self::color('yellow') . self::toLine($expected) . self::color('white'),
			]);
			if ($e->getPrevious()) {
				$message .= static::dumpException($e->getPrevious());
			}
		} else {
			$message = ($e instanceof \ErrorException ? Helpers::errorTypeToString($e->getSeverity()) : get_class($e))
				. ': ' . preg_replace('#[\x00-\x09\x0B-\x1F]+#', ' ', $e->getMessage()) . "\n";

			foreach ($trace as $item) {
				$item += ['file' => null, 'class' => null, 'type' => null, 'function' => null];
				if ($e instanceof AssertException && $item['file'] === __DIR__ . DIRECTORY_SEPARATOR . 'Assert.php') {
					continue;
				}
				$line = $item['class'] === Assert::class && method_exists($item['class'], $item['function'])
				&& strpos($tmp = file($item['file'])[$item['line'] - 1], "::$item[function](") ? $tmp : null;

				$message .= '                     in '
					. ($item['file']
						? (
							($item['file'] === $testFile ? self::color('white') : '')
							. implode(
								self::$pathSeparator ?? DIRECTORY_SEPARATOR,
								array_slice(explode(DIRECTORY_SEPARATOR, $item['file']), -self::$maxPathSegments)
							)
							. "($item[line])" . self::color('gray') . ' '
						)
						: '[internal function]'
					)
					. ($line
						? trim($line)
						: $item['class'] . $item['type'] . $item['function'] . ($item['function'] ? '()' : '')
					)
					. self::color() . "\n";
			}

			if ($e->getPrevious()) {
				$message .= "\n(previous) " . static::dumpException($e->getPrevious());
			}
		}

		return self::color('white', $message) . "\n";
	}
}
