<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Tester;

use Tester\Dumper;
use Tester\Runner\OutputHandler;
use Tester\Runner\Runner;
use Tester\Runner\Test;


class UrlPrinter implements OutputHandler
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
		private array $dataProvider,
		private bool $displaySkipped = false,
		string $file = 'php://output',
		private bool $ciderMode = false,
		private array $modules = []
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
		foreach ($this->modules as $name => $config) {
			$m .= Dumper::color($config['color'], $config['icon']) . ' ' . $name . ' ';
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

		$dataProvider = substr(
			$test->getSignature(),
			strpos($test->getSignature(), '=') + 1,
			strpos($test->getSignature(), '|') - strpos($test->getSignature(), '=') - 1
		);

		$moduleName = substr(
			$dataProvider,
			0,
			strpos($dataProvider, '-') - 1,
		);

		$requestCount = $this->countRequests($this->dataProvider[$dataProvider]);
		$this->totalRequestCount += $requestCount;

		if (isset($this->modules[$moduleName])) {
			$dataProviderWithIcon = str_replace(
				$moduleName . ' -',
				Dumper::color($this->modules[$moduleName]['color'], $this->modules[$moduleName]['icon']),
				$dataProvider
			);
		}

		$write = $this->symbols[$test->getResult()]
			. ' '
			. Dumper::color('yellow', sprintf('%0.2f', round((float) $test->getDuration(), 2)) . 's')
			. ' '
			. Dumper::color('olive', sprintf('%2u', $requestCount) . '×')
			. ' '
			. ($dataProviderWithIcon ?? $dataProvider)
			. "\n";

		$message = str_replace("\n", "\n   ", trim((string) $test->message)) . "\n";
		if ($test->getResult() === Test::FAILED) {
			$write .= Dumper::color('red', "                ") . "$message";
		} elseif ($test->getResult() === Test::SKIPPED && $this->displaySkipped) {
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
			. ($this->results[Test::FAILED] ? Dumper::color('white/red') . 'FAILURES!' : Dumper::color('white/green') . 'OK')
			. " ($this->count test" . ($this->count > 1 ? 's' : '') . ', '
			. ($this->results[Test::FAILED] ? $this->results[Test::FAILED] . ' failure' . ($this->results[Test::FAILED] > 1 ? 's' : '') . ', ' : '')
			. ($this->results[Test::SKIPPED] ? $this->results[Test::SKIPPED] . ' skipped, ' : '')
			. "$this->totalRequestCount requests, "
			. ($this->count !== $run ? ($this->count - $run) . ' not run, ' : '')
			. sprintf('%0.1f', $this->time + microtime(true)) . ' seconds)' . Dumper::color() . "\n");

		$this->buffer = null;
	}


	private function countRequests($dataProvider)
	{
		$c = 0;
		if (!isset($dataProvider['requests'])) {
			return 1;
		}
		foreach ($dataProvider['requests'] as $s) {
			$c += $this->countRequests($s);
		}
		return $c;
	}
}
