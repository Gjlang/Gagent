<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class AppiumAndroidTestService
{
    private string $appiumRoot;

    public function __construct()
    {
        $this->appiumRoot = base_path(
            '../phase8_android_dummy_app/appium'
        );
    }

    public function run(array $input): array
    {
        $testMode = trim(
            (string) ($input['test_mode'] ?? '')
        );

        $testRunId = (string) (
            $input['test_run_id'] ?? 'manual'
        );

        $flowType = trim(
            (string) (
                $input['flow_type']
                ?? 'basic_navigation'
            )
        );

        $packageName = trim(
            (string) (
                $input['target_app_package']
                ?? $input['app_package']
                ?? ''
            )
        );

        $activityName = trim(
            (string) (
                $input['target_app_activity']
                ?? $input['app_activity']
                ?? ''
            )
        );

        $deviceName = trim(
            (string) (
                $input['device_name']
                ?? 'emulator-5554'
            )
        );

        $apkPath = trim(
            (string) (
                $input['apk_path'] ?? ''
            )
        );

        $maxDuration = (int) (
            $input['max_duration_seconds'] ?? 60
        );

        $allowedModes = [
            'dummy_app',
            'real_apk',
            'installed_app',
        ];

        $allowedFlows = [
            'basic_navigation',
            'button_click',
            'form_input',
            'search_flow',
        ];

        if (!in_array($testMode, $allowedModes, true)) {
            return $this->error(
                'Invalid Android test mode.'
            );
        }

        if (!in_array($flowType, $allowedFlows, true)) {
            return $this->error(
                'Invalid Android flow type.'
            );
        }

        if (
            $maxDuration < 10
            || $maxDuration > 180
        ) {
            return $this->error(
                'Maximum duration must be between 10 and 180 seconds.'
            );
        }

        if (
            in_array(
                $testMode,
                ['dummy_app', 'real_apk'],
                true
            )
        ) {
            if (
                $apkPath === ''
                || !is_file($apkPath)
            ) {
                return $this->error(
                    'Android APK not found: ' . $apkPath
                );
            }

            if (
                strtolower(
                    pathinfo(
                        $apkPath,
                        PATHINFO_EXTENSION
                    )
                ) !== 'apk'
            ) {
                return $this->error(
                    'The selected Android file must be an APK.'
                );
            }
        }

        if (
            $testMode === 'installed_app'
            && $packageName === ''
        ) {
            return $this->error(
                'Package name is required for installed app mode.'
            );
        }

        $scriptPath = $this->appiumRoot
            . DIRECTORY_SEPARATOR
            . 'test_real_android_app.py';

        if (!is_file($scriptPath)) {
            return $this->error(
                'Generic Android Appium runner not found: '
                . $scriptPath
            );
        }

        $pythonCommand = (string) env(
            'GAGENT_APPIUM_PYTHON',
            'python'
        );

        $appiumUrl = (string) env(
            'GAGENT_APPIUM_URL',
            'http://127.0.0.1:4723'
        );

        $command = [
            $pythonCommand,
            $scriptPath,

            '--mode',
            $testMode,

            '--flow',
            $flowType,

            '--device',
            $deviceName,

            '--testRunId',
            $testRunId,

            '--maxDuration',
            (string) $maxDuration,

            '--appium-url',
            $appiumUrl,
        ];

        if ($apkPath !== '') {
            $command[] = '--apk';
            $command[] = $apkPath;
        }

        if ($packageName !== '') {
            $command[] = '--package';
            $command[] = $packageName;
        }

        if ($activityName !== '') {
            $command[] = '--activity';
            $command[] = $activityName;
        }

        $environment = $this->buildEnvironment();

        $process = new Process(
            $command,
            $this->appiumRoot,
            $environment
        );

        $process->setTimeout(
            $maxDuration + 90
        );

        $process->setIdleTimeout(
            $maxDuration + 60
        );

        try {
            $process->run();

            $stdout = trim(
                $process->getOutput()
            );

            $stderr = trim(
                $process->getErrorOutput()
            );

            $exitCode = $process->getExitCode();

            $decoded = $this->parseJsonOutput(
                $stdout
            );

            if (!$decoded) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => (
                        'The Android runner did not return valid JSON. '
                        . $this->bestError(
                            $stderr,
                            $stdout
                        )
                    ),
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'metrics' => null,
                ];
            }

            $runnerStatus = (
                $decoded['status'] ?? 'error'
            );

            if (
                !in_array(
                    $runnerStatus,
                    [
                        'success',
                        'controlled_failure',
                    ],
                    true
                )
            ) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => (
                        $decoded['message']
                        ?? 'Android Appium execution failed.'
                    ),
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'metrics' => null,
                    'runner_data' => $decoded,
                ];
            }

            if (
                !isset($decoded['metrics'])
                || !is_array($decoded['metrics'])
            ) {
                return [
                    'status' => 'error',
                    'exit_code' => $exitCode,
                    'message' => (
                        'The Android runner returned no metrics.'
                    ),
                    'stdout' => $stdout,
                    'stderr' => $stderr,
                    'metrics' => null,
                    'runner_data' => $decoded,
                ];
            }

            return [
                'status' => 'success',
                'runner_status' => $runnerStatus,
                'exit_code' => $exitCode,
                'message' => (
                    $decoded['message']
                    ?? 'Android test completed.'
                ),
                'stdout' => $stdout,
                'stderr' => $stderr,
                'metrics' => $decoded['metrics'],
                'runner_data' => $decoded,
            ];

        } catch (ProcessTimedOutException $error) {
            return [
                'status' => 'error',
                'exit_code' => null,
                'message' => (
                    'Android Appium test exceeded its allowed execution time.'
                ),
                'stdout' => trim(
                    $process->getOutput()
                ),
                'stderr' => trim(
                    $process->getErrorOutput()
                ),
                'metrics' => null,
            ];

        } catch (Throwable $error) {
            return [
                'status' => 'error',
                'exit_code' => null,
                'message' => (
                    'Laravel could not run the Android Appium script: '
                    . $error->getMessage()
                ),
                'stdout' => '',
                'stderr' => '',
                'metrics' => null,
            ];
        }
    }

    private function parseJsonOutput(
        string $stdout
    ): ?array {
        if ($stdout === '') {
            return null;
        }

        $lines = preg_split(
            '/\r\n|\r|\n/',
            $stdout
        );

        $lines = array_values(
            array_filter(
                $lines,
                fn ($line) => trim($line) !== ''
            )
        );

        for (
            $index = count($lines) - 1;
            $index >= 0;
            $index--
        ) {
            $candidate = trim($lines[$index]);

            $decoded = json_decode(
                $candidate,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
            ) {
                return $decoded;
            }
        }

        $decoded = json_decode(
            $stdout,
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($decoded)
        ) {
            return $decoded;
        }

        return null;
    }

    private function buildEnvironment(): array
    {
        return [
            'PATH' => (
                getenv('PATH')
                ?: ($_SERVER['PATH'] ?? '')
            ),

            'Path' => (
                getenv('Path')
                ?: ($_SERVER['Path'] ?? '')
            ),

            'SYSTEMROOT' => (
                getenv('SYSTEMROOT')
                ?: 'C:\Windows'
            ),

            'SystemRoot' => (
                getenv('SystemRoot')
                ?: 'C:\Windows'
            ),

            'WINDIR' => (
                getenv('WINDIR')
                ?: 'C:\Windows'
            ),

            'USERPROFILE' => (
                getenv('USERPROFILE')
                ?: ($_SERVER['USERPROFILE'] ?? '')
            ),

            'LOCALAPPDATA' => (
                getenv('LOCALAPPDATA')
                ?: ($_SERVER['LOCALAPPDATA'] ?? '')
            ),

            'APPDATA' => (
                getenv('APPDATA')
                ?: ($_SERVER['APPDATA'] ?? '')
            ),

            'ANDROID_HOME' => (
                getenv('ANDROID_HOME')
                ?: ($_SERVER['ANDROID_HOME'] ?? '')
            ),

            'ANDROID_SDK_ROOT' => (
                getenv('ANDROID_SDK_ROOT')
                ?: ($_SERVER['ANDROID_SDK_ROOT'] ?? '')
            ),
        ];
    }

    private function bestError(
        string $stderr,
        string $stdout
    ): string {
        $message = (
            $stderr !== ''
                ? $stderr
                : $stdout
        );

        $message = preg_replace(
            '/\s+/',
            ' ',
            $message ?? ''
        );

        if (!$message) {
            return 'No error output was returned.';
        }

        return trim($message);
    }

    private function error(
        string $message
    ): array {
        return [
            'status' => 'error',
            'exit_code' => null,
            'message' => $message,
            'stdout' => '',
            'stderr' => '',
            'metrics' => null,
        ];
    }
}
