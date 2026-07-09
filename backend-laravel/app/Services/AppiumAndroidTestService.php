<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AppiumAndroidTestService
{
    private string $appiumRoot;

    public function __construct()
    {
        $this->appiumRoot = base_path('../phase8_android_dummy_app/appium');
    }

    public function run(array $input): array
    {
        $testRunId = (string) ($input['test_run_id'] ?? 'manual');
        $flowType = (string) ($input['flow_type'] ?? 'login');
        $scenarioType = (string) ($input['scenario_type'] ?? 'good');

        $apkPath = $input['apk_path'] ?? base_path('../phase8_android_dummy_app/app/build/outputs/apk/debug/app-debug.apk');
        $scriptPath = $this->appiumRoot . DIRECTORY_SEPARATOR . 'test_dummy_android_app.py';

        if (!file_exists($scriptPath)) {
            throw new RuntimeException('Appium Python script not found: ' . $scriptPath);
        }

        if (!file_exists($apkPath)) {
            throw new RuntimeException('Android APK not found: ' . $apkPath);
        }

        $outputDir = storage_path('app/appium-results');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . 'android-run-' . $testRunId . '.csv';

        $pythonCommand = env('GAGENT_APPIUM_PYTHON', 'python');

        $command = [
            $pythonCommand,
            $scriptPath,
            '--app',
            $apkPath,
            '--repeat',
            '1',
            '--out',
            $outputPath,
        ];

        $env = [
            'PATH' => getenv('PATH') ?: ($_SERVER['PATH'] ?? ''),
            'Path' => getenv('Path') ?: ($_SERVER['Path'] ?? ''),
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
            'USERPROFILE' => getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? ''),
            'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: ($_SERVER['LOCALAPPDATA'] ?? ''),
            'APPDATA' => getenv('APPDATA') ?: ($_SERVER['APPDATA'] ?? ''),
            'ANDROID_HOME' => getenv('ANDROID_HOME') ?: ($_SERVER['ANDROID_HOME'] ?? ''),
            'ANDROID_SDK_ROOT' => getenv('ANDROID_SDK_ROOT') ?: ($_SERVER['ANDROID_SDK_ROOT'] ?? ''),
        ];

        $process = new Process($command, $this->appiumRoot, $env);
        $process->setTimeout(300);
        $process->setIdleTimeout(300);

        try {
            $process->run();

            $stdout = trim($process->getOutput());
            $stderr = trim($process->getErrorOutput());
            $exitCode = $process->getExitCode();

            if (!$process->isSuccessful()) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => 'Appium script failed.',
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'raw_metrics_path' => $outputPath,
                    'metrics' => null,
                ];
            }

            if (!file_exists($outputPath)) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => 'Appium completed but CSV output was not created.',
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'raw_metrics_path' => $outputPath,
                    'metrics' => null,
                ];
            }

            $rows = $this->readCsv($outputPath);

            $selectedRow = collect($rows)->first(function ($row) use ($flowType, $scenarioType) {
                return ($row['flow_type'] ?? null) === $flowType
                    && ($row['scenario_type'] ?? null) === $scenarioType;
            });

            if (!$selectedRow) {
                $selectedRow = collect($rows)->firstWhere('flow_type', $flowType);
            }

            if (!$selectedRow) {
                $selectedRow = $rows[0] ?? null;
            }

            if (!$selectedRow) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => 'Appium CSV exists but no metric rows were found.',
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'raw_metrics_path' => $outputPath,
                    'metrics' => null,
                ];
            }

            return [
                'status' => 'success',
                'exit_code' => $exitCode,
                'message' => 'Appium test completed.',
                'stdout' => $stdout,
                'stderr' => $stderr,
                'raw_metrics_path' => $outputPath,
                'metrics' => $selectedRow,
            ];
        } catch (Throwable $error) {
            return [
                'status' => 'error',
                'exit_code' => null,
                'message' => 'Laravel could not execute Appium script: ' . $error->getMessage(),
                'stdout' => '',
                'stderr' => '',
                'raw_metrics_path' => $outputPath,
                'metrics' => null,
            ];
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new RuntimeException('Could not read CSV file: ' . $path);
        }

        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);
            return [];
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
