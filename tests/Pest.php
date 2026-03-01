<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function step(string $name, Closure $closure)
{
    $start = microtime(true);
    $status = 'PASS';
    $errorMessage = null;

    try {
        $closure();
    } catch (\Throwable $e) {
        $status = 'FAIL';
        $errorMessage = $e->getMessage();
        throw $e;
    } finally {
        $duration = round((microtime(true) - $start) * 1000, 2);
        $timestamp = now()->format('H:i:s');
        
        // Find the actual test file in the backtrace
        $file = 'unknown';
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['file']) && str_ends_with($trace['file'], 'ScenarioTest.php')) {
                $file = basename($trace['file']);
                break;
            }
        }

        $stepData = [
            'file' => $file,
            'step' => $name,
            'status' => $status,
            'duration_ms' => $duration,
            'timestamp' => $timestamp,
            'error' => $errorMessage
        ];

        $logPath = storage_path('logs/test_steps.jsonl');
        file_put_contents($logPath, json_encode($stepData) . PHP_EOL, FILE_APPEND);
        
        // Use a very specific prefix and ensure it's on a new line
        echo PHP_EOL . "##STEP_DATA##" . json_encode($stepData) . "##" . PHP_EOL;
    }
}


