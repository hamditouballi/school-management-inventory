<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Pest tests and automatically generate HTML and JSON reports.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Pest test suite...');

        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportDir = storage_path("test-reports/{$timestamp}");
        
        if (!File::exists($reportDir)) {
            File::makeDirectory($reportDir, 0755, true);
        }

        $htmlReportPath = "{$reportDir}/report.html";
        $xmlReportPath = "{$reportDir}/report.xml";
        $jsonReportPath = "{$reportDir}/report.json";

        // Force testing environment to avoid confirmation prompts in production
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        // Build command to run tests
        // We use --testdox-html and --log-junit via the underlying pest/phpunit options
        $command = "php artisan test --testdox-html={$htmlReportPath} --log-junit={$xmlReportPath}";

        // Execute tests
        $this->info("Running tests...");
        
        // Clear old steps log
        $logPath = storage_path('logs/test_steps.jsonl');
        if (File::exists($logPath)) {
            File::delete($logPath);
        }

        $process = proc_open($command, [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ], $pipes);

        $scenarioSteps = [];

        if (is_resource($process)) {
            while ($line = fgets($pipes[1])) {
                if (preg_match_all('/##STEP_DATA##(.*?)##/', $line, $matches)) {
                    foreach ($matches[1] as $json) {
                        $data = json_decode($json, true);
                        if ($data) {
                            $scenarioSteps[] = $data;
                            $color = $data['status'] === 'PASS' ? 'info' : 'error';
                            $this->line("  - {$data['step']} -> {$data['timestamp']} -> {$data['duration_ms']}ms -> <{$color}>{$data['status']}</{$color}>");
                        }
                    }
                    $cleanLine = preg_replace('/##STEP_DATA##.*?##/', '', $line);
                    if (trim($cleanLine)) {
                        echo $cleanLine;
                    }
                    continue;
                }
                echo $line;
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            $resultCode = proc_close($process);
        }



        $this->newLine();
        
        if ($resultCode === 0) {
            $this->info('Test suite passed successfully!');
        } else {
            $this->error('Test suite failed or encountered errors.');
        }

        // Reload all steps from log file to ensure completeness (in case some were missed in real-time console parsing)
        $scenarioSteps = [];
        if (File::exists($logPath)) {
            $lines = explode(PHP_EOL, trim(File::get($logPath)));
            foreach ($lines as $line) {
                if ($data = json_decode($line, true)) {
                    $scenarioSteps[] = $data;
                }
            }
        }

        // Convert XML to JSON
        if (File::exists($xmlReportPath)) {
            $this->info("Converting XML report to JSON...");
            $xmlContent = simplexml_load_file($xmlReportPath);
            if ($xmlContent !== false) {
                // Merge steps into JSON report
                $jsonArray = json_decode(json_encode($xmlContent), true);
                $jsonArray['scenario_steps'] = $scenarioSteps;
                File::put($jsonReportPath, json_encode($jsonArray, JSON_PRETTY_PRINT));
                $this->info("JSON report generated successfully.");
            }
        }

        // Generate Custom HTML Report
        $this->info("Generating custom HTML report...");
        $html = $this->generateCustomHtml($scenarioSteps, $timestamp, $resultCode === 0);
        File::put($htmlReportPath, $html);


        $this->newLine();
        $this->info('--- Report Summary ---');
        $this->line("Directory: {$reportDir}");
        $this->line("- HTML Report: " . basename($htmlReportPath));
        $this->line("- JSON Report: " . basename($jsonReportPath));
        if (File::exists($xmlReportPath)) {
            $this->line("- XML Report:  " . basename($xmlReportPath));
        }
        
        $this->newLine();
    }

    private function generateCustomHtml($steps, $timestamp, $passed)
    {
        $statusColor = $passed ? '#10b981' : '#ef4444';
        $statusText = $passed ? 'PASSED' : 'FAILED';
        
        // Group steps by file
        $grouped = [];
        foreach ($steps as $step) {
            $grouped[$step['file']][] = $step;
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>E2E Test Report - <?php echo $timestamp; ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f9fafb; color: #111827; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .header { padding: 2rem; background: #1f2937; color: white; display: flex; justify-content: space-between; align-items: center; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; background: <?php echo $statusColor; ?>; }
        .scenario-card { border-bottom: 1px solid #e5e7eb; }
        .scenario-header { padding: 1.5rem 2rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f3f4f6; }
        .scenario-header:hover { background: #e5e7eb; }
        .scenario-content { display: none; padding: 1rem 2rem; background: white; }
        .step-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; }
        .step-row:last-child { border-bottom: none; }
        .pass { color: #10b981; font-weight: bold; }
        .fail { color: #ef4444; font-weight: bold; }
        .meta { color: #6b7280; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 style="margin:0">Test Scenario Report</h1>
                <p style="margin:0.5rem 0 0; opacity: 0.8">Executed at: <?php echo $timestamp; ?></p>
            </div>
            <div class="status-badge"><?php echo $statusText; ?></div>
        </div>
        
        <?php foreach ($grouped as $file => $fileSteps): ?>
        <div class="scenario-card">
            <div class="scenario-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'">
                <h3 style="margin:0"><?php echo str_replace('ScenarioTest.php', '', $file); ?> Workflow</h3>
                <span class="meta"><?php echo count($fileSteps); ?> Steps</span>
            </div>
            <div class="scenario-content">
                <?php foreach ($fileSteps as $step): ?>
                <div class="step-row">
                    <div>
                        <span class="<?php echo strtolower($step['status']); ?>">
                            <?php echo $step['status'] === 'PASS' ? '✓' : '✗'; ?>
                        </span>
                        <span style="margin-left: 1rem"><?php echo $step['step']; ?></span>
                    </div>
                    <div class="meta">
                        <span><?php echo $step['timestamp']; ?></span>
                        <span style="margin-left: 1.5rem"><?php echo $step['duration_ms']; ?> ms</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}

