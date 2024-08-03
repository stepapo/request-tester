<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Tester;

use Tester\Dumper;
use Tester\Runner\OutputHandler;
use Tester\Runner\Runner;
use Tester\Runner\Test;


class Printer implements OutputHandler
{
	private $file;

	private ?string $buffer = null;

	private float $time;

	private array $results;

	private int $count;

	private ?string $baseDir = null;

	private array $symbols;

	private int $totalRequestCount = 0;


	public function __construct(
		private Runner $runner,
		private array $configs,
		private bool $displaySkipped = false,
		string $file = 'php://output',
		private bool $ciderMode = false,
		private array $setups = []
	) {
		$this->file = fopen($file, 'w');
		$this->symbols = [
			Test::PASSED => $ciderMode ? Dumper::color('green', '🍎') : '.',
			Test::SKIPPED => 's',
			Test::FAILED => $ciderMode ? Dumper::color('red', '🍎') : Dumper::color('white/red', 'F'),
		];
	}


	public function begin(): void
	{
		$this->count = 0;
		$this->baseDir = null;
		$this->results = [
			Test::PASSED => 0,
			Test::SKIPPED => 0,
			Test::FAILED => 0,
		];
		$this->time = -microtime(true);
		$m = '';
		foreach ($this->setups as $name => $setup) {
			$m .= Dumper::color($setup['color'], $setup['icon']) . ' ' . $name . ' ';
		}
		fwrite($this->file, $this->runner->getInterpreter()->getShortInfo()
			. ' | ' . $this->runner->getInterpreter()->getCommandLine()
			. " | {$this->runner->threadCount} thread" . ($this->runner->threadCount > 1 ? 's' : '') . "\n\n"
			. ($m ? $m . "\n\n" : "")
		);
	}


	public function prepare(Test $test): void
	{
		if ($this->baseDir === null) {
			$this->baseDir = dirname($test->getFile()) . DIRECTORY_SEPARATOR;
		} elseif (strpos($test->getFile(), $this->baseDir) !== 0) {
			$common = array_intersect_assoc(
				explode(DIRECTORY_SEPARATOR, $this->baseDir),
				explode(DIRECTORY_SEPARATOR, $test->getFile())
			);
			$this->baseDir = '';
			$prev = 0;
			foreach ($common as $i => $part) {
				if ($i !== $prev++) {
					break;
				}
				$this->baseDir .= $part . DIRECTORY_SEPARATOR;
			}
		}

		$this->count++;
	}


	public function finish(Test $test): void
	{
		$this->results[$test->getResult()]++;

		$config = substr(
			$test->getSignature(),
			strpos($test->getSignature(), '=') + 1,
			strpos($test->getSignature(), '|') - strpos($test->getSignature(), '=') - 1
		);

		$moduleName = substr(
			$config,
			0,
			strpos($config, '-') - 1,
		);

		$requestCount = $this->countRequests($this->configs[$config]);
		$this->totalRequestCount += $requestCount;

		if (isset($this->setups[$moduleName])) {
			$configWithIcon = str_replace(
				$moduleName . ' -',
				Dumper::color($this->setups[$moduleName]['color'], $this->setups[$moduleName]['icon']),
				$config
			);
		}

		$write = $this->symbols[$test->getResult()]
			. ' '
			. Dumper::color('aqua', sprintf('%0.3f', round((float) $test->getDuration(), 3)) . 's')
			. ' '
			. Dumper::color('teal', sprintf('%2u', $requestCount) . '×')
			. ' '
			. ($configWithIcon ?? $config)
			. "\n";

		$message = str_replace("\n", "\n   ", trim((string) $test->message)) . "\n";
		if ($test->getResult() === Test::Failed) {
			$write .= Dumper::color('red', "                 ") . "$message";
		} elseif ($test->getResult() === Test::Skipped && $this->displaySkipped) {
			$write .= "   Skipped: $message";
		}

		fwrite($this->file, $write);
	}


	public function end(): void
	{
		$run = array_sum($this->results);

		fwrite($this->file, !$this->count ? "No tests found\n" :
			"\n"
			. ($this->buffer ? "\n" . $this->buffer . "\n" : "")
			. ($this->results[Test::Failed] ? Dumper::color('red') . 'FAILURES!' : Dumper::color('green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::Failed] ? $this->results[Test::Failed] . ' failure' . ($this->results[Test::Failed] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::Skipped] ? $this->results[Test::Skipped] . ' skipped, ' : '')
			. "$this->totalRequestCount requests, "
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(true)) . ' seconds)' . Dumper::color() . "\n");

		$this->buffer = null;
	}


	private function countRequests($config)
	{
		$c = 0;
		if (!isset($config['requests'])) {
			return 1;
		}
		foreach ($config['requests'] as $s) {
			$c += $this->countRequests($s);
		}
		return $c;
	}
}
