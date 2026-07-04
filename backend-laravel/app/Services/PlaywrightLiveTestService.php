<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PlaywrightLiveTestService
{
    private string $playwrightRoot;

    public function __construct()
    {
        $this->playwrightRoot = base_path('../phase3_web_automation/playwright_agent');
    }

    public function run(array $input): array
    {
        $targetUrl = $input['target_url'] ?? '';
        $flowType = $input['flow_type'] ?? '';
        $viewportType = $input['viewport_type'] ?? '';
        $networkCondition = $input['network_condition'] ?? '';
        $maxDurationSeconds = (int) ($input['max_duration_seconds'] ?? 60);
        $testRunId = (string) ($input['test_run_id'] ?? '');

        $this->validateInput(
            $targetUrl,
            $flowType,
            $viewportType,
            $networkCondition,
            $maxDurationSeconds
        );

        $scriptPath = $this->playwrightRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run-live-test.js';

        if (!file_exists($scriptPath)) {
            throw new RuntimeException('Playwright live test script not found: ' . $scriptPath);
        }

        $command = [
            'node',
            $scriptPath,
            '--url',
            $targetUrl,
            '--flow',
            $flowType,
            '--viewport',
            $viewportType,
            '--network',
            $networkCondition,
            '--testRunId',
            $testRunId,
            '--maxDuration',
            (string) $maxDurationSeconds,
        ];

        $tempDir = storage_path('app/playwright-temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

      $env = [
    'PATH' => getenv('PATH') ?: ($_SERVER['PATH'] ?? ''),
    'Path' => getenv('Path') ?: ($_SERVER['Path'] ?? ''),
    'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
    'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
    'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',

    'USERPROFILE' => getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? ''),
    'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: ($_SERVER['LOCALAPPDATA'] ?? ''),
    'APPDATA' => getenv('APPDATA') ?: ($_SERVER['APPDATA'] ?? ''),

    'TEMP' => $tempDir,
    'TMP' => $tempDir,
    'TMPDIR' => $tempDir,

    'NO_COLOR' => '1',
];
        $process = new Process($command, $this->playwrightRoot, $env);
        $process->setTimeout($maxDurationSeconds + 20);
        $process->setIdleTimeout($maxDurationSeconds + 20);

        try {
            $process->run();

            $stdout = trim($process->getOutput());
            $stderr = trim($process->getErrorOutput());
            $exitCode = $process->getExitCode();

            $decoded = json_decode($stdout, true);

            if (!is_array($decoded)) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => 'Playwright did not return valid JSON.',
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'data' => null,
                ];
            }

            return [
                'status' => $process->isSuccessful() && (($decoded['status'] ?? null) === 'success')
                    ? 'success'
                    : 'error',
                'exit_code' => $exitCode,
                'message' => $decoded['message'] ?? ($process->isSuccessful() ? 'Playwright completed.' : 'Playwright failed.'),
                'stdout' => $stdout,
                'stderr' => $stderr,
                'data' => $decoded,
            ];
        } catch (Throwable $error) {
            return [
                'status' => 'error',
                'exit_code' => null,
                'message' => 'Laravel could not execute Playwright: ' . $error->getMessage(),
                'stdout' => '',
                'stderr' => '',
                'data' => null,
            ];
        }
    }

    private function validateInput(
        string $targetUrl,
        string $flowType,
        string $viewportType,
        string $networkCondition,
        int $maxDurationSeconds
    ): void {
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Invalid target URL.');
        }

        $scheme = parse_url($targetUrl, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Only http and https URLs are allowed.');
        }

        if (!in_array($flowType, ['landing_navigation', 'cta_click', 'basic_search'], true)) {
            throw new RuntimeException('Invalid flow type.');
        }

        if (!in_array($viewportType, ['desktop', 'tablet', 'mobile'], true)) {
            throw new RuntimeException('Invalid viewport type.');
        }

        if (!in_array($networkCondition, ['normal', 'slow'], true)) {
            throw new RuntimeException('Invalid network condition.');
        }

        if ($maxDurationSeconds < 10 || $maxDurationSeconds > 120) {
            throw new RuntimeException('Max duration must be between 10 and 120 seconds.');
        }
    }
}
