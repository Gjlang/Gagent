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
        $this->playwrightRoot = base_path(
            '../phase3_web_automation/playwright_agent'
        );
    }

    public function run(array $input): array
    {
        $targetUrl = (string) ($input['target_url'] ?? '');
        $flowType = (string) ($input['flow_type'] ?? 'auto');
        $viewportType = (string) ($input['viewport_type'] ?? 'desktop');
        $networkCondition = (string) ($input['network_condition'] ?? 'normal');

        $maxDurationSeconds = (int) (
            $input['max_duration_seconds'] ?? 60
        );

        $testRunId = (string) (
            $input['test_run_id'] ?? ''
        );

        $showBrowser = (bool) (
            $input['show_browser'] ?? false
        );

        $slowMoMs = (int) (
            $input['slow_mo_ms'] ?? 0
        );

        $this->validateInput(
            $targetUrl,
            $flowType,
            $viewportType,
            $networkCondition,
            $maxDurationSeconds,
            $slowMoMs
        );

        $scriptName = $flowType === 'full_audit'
            ? 'run-full-audit.js'
            : 'run-live-test.js';

        $scriptPath =
            $this->playwrightRoot
            . DIRECTORY_SEPARATOR
            . 'scripts'
            . DIRECTORY_SEPARATOR
            . $scriptName;

        if (!file_exists($scriptPath)) {
            throw new RuntimeException(
                'Playwright script not found: ' . $scriptPath
            );
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

            '--headed',
            $showBrowser ? '1' : '0',

            '--slowMo',
            (string) $slowMoMs,
        ];

        $tempDir = storage_path(
            'app/playwright-temp'
        );

        if (!is_dir($tempDir)) {
            mkdir(
                $tempDir,
                0775,
                true
            );
        }

        $env = [
            'PATH' =>
                getenv('PATH')
                ?: ($_SERVER['PATH'] ?? ''),

            'Path' =>
                getenv('Path')
                ?: ($_SERVER['Path'] ?? ''),

            'SYSTEMROOT' =>
                getenv('SYSTEMROOT')
                ?: 'C:\\Windows',

            'SystemRoot' =>
                getenv('SystemRoot')
                ?: 'C:\\Windows',

            'WINDIR' =>
                getenv('WINDIR')
                ?: 'C:\\Windows',

            'USERPROFILE' =>
                getenv('USERPROFILE')
                ?: ($_SERVER['USERPROFILE'] ?? ''),

            'LOCALAPPDATA' =>
                getenv('LOCALAPPDATA')
                ?: ($_SERVER['LOCALAPPDATA'] ?? ''),

            'APPDATA' =>
                getenv('APPDATA')
                ?: ($_SERVER['APPDATA'] ?? ''),

            'TEMP' => $tempDir,
            'TMP' => $tempDir,
            'TMPDIR' => $tempDir,
            'NO_COLOR' => '1',
        ];

        $process = new Process(
            $command,
            $this->playwrightRoot,
            $env
        );

        $process->setTimeout(
            $maxDurationSeconds + 30
        );

        $process->setIdleTimeout(
            $maxDurationSeconds + 30
        );

        try {
            $process->run();

            $stdout = trim(
                $process->getOutput()
            );

            $stderr = trim(
                $process->getErrorOutput()
            );

            $exitCode =
                $process->getExitCode();

            $decoded = json_decode(
                $stdout,
                true
            );

            if (!is_array($decoded)) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' =>
                        'Playwright did not return valid JSON.',
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'data' => null,
                ];
            }

            $successful =
                $process->isSuccessful()
                && (($decoded['status'] ?? null) === 'success');

            return [
                'status' =>
                    $successful
                    ? 'success'
                    : 'error',

                'exit_code' => $exitCode,

                'message' =>
                    $decoded['message']
                    ?? (
                        $successful
                        ? 'Playwright completed.'
                        : 'Playwright failed.'
                    ),

                'stdout' => $stdout,
                'stderr' => $stderr,
                'data' => $decoded,
            ];
        } catch (Throwable $error) {
            return [
                'status' => 'error',
                'exit_code' => null,

                'message' =>
                    'Laravel could not execute Playwright: '
                    . $error->getMessage(),

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
        int $maxDurationSeconds,
        int $slowMoMs
    ): void {
        if (
            !filter_var(
                $targetUrl,
                FILTER_VALIDATE_URL
            )
        ) {
            throw new RuntimeException(
                'Invalid target URL.'
            );
        }

        $scheme = parse_url(
            $targetUrl,
            PHP_URL_SCHEME
        );

        if (
            !in_array(
                $scheme,
                ['http', 'https'],
                true
            )
        ) {
            throw new RuntimeException(
                'Only HTTP and HTTPS URLs are allowed.'
            );
        }

        $allowedFlows = [
            'full_audit',
            'auto',
            'landing_navigation',
            'cta_click',
            'basic_search',
        ];

        if (
            !in_array(
                $flowType,
                $allowedFlows,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid flow type.'
            );
        }

        if (
            !in_array(
                $viewportType,
                ['desktop', 'tablet', 'mobile'],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid viewport type.'
            );
        }

        if (
            !in_array(
                $networkCondition,
                ['normal', 'slow'],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid network condition.'
            );
        }

        $maximumDuration =
            $flowType === 'full_audit'
            ? 300
            : 120;

        if (
            $maxDurationSeconds < 10
            || $maxDurationSeconds > $maximumDuration
        ) {
            throw new RuntimeException(
                "Max duration must be between 10 and {$maximumDuration} seconds."
            );
        }

        if (
            $slowMoMs < 0
            || $slowMoMs > 1000
        ) {
            throw new RuntimeException(
                'Playwright action delay must be between 0 and 1000 milliseconds.'
            );
        }
    }
}
