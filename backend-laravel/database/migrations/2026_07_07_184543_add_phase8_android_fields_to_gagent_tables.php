<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('test_runs', 'platform')) {
                $table->string('platform')->default('web');
            }

            if (!Schema::hasColumn('test_runs', 'target_type')) {
                $table->string('target_type')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'target_app_package')) {
                $table->string('target_app_package')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'target_app_activity')) {
                $table->string('target_app_activity')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'apk_path')) {
                $table->text('apk_path')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'device_name')) {
                $table->string('device_name')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'automation_driver')) {
                $table->string('automation_driver')->nullable();
            }

            if (!Schema::hasColumn('test_runs', 'appium_exit_code')) {
                $table->integer('appium_exit_code')->nullable();
            }
        });

        Schema::table('ux_metrics', function (Blueprint $table) {
            if (!Schema::hasColumn('ux_metrics', 'device_type')) {
                $table->string('device_type')->nullable();
            }

            if (!Schema::hasColumn('ux_metrics', 'platform_name')) {
                $table->string('platform_name')->nullable();
            }

            if (!Schema::hasColumn('ux_metrics', 'app_launch_time_ms')) {
                $table->double('app_launch_time_ms')->default(0);
            }

            if (!Schema::hasColumn('ux_metrics', 'screen_load_time_ms')) {
                $table->double('screen_load_time_ms')->default(0);
            }

            if (!Schema::hasColumn('ux_metrics', 'interaction_response_time_ms')) {
                $table->double('interaction_response_time_ms')->default(0);
            }

            if (!Schema::hasColumn('ux_metrics', 'finish_time_ms')) {
                $table->double('finish_time_ms')->default(0);
            }

            if (!Schema::hasColumn('ux_metrics', 'overlay_blocks_action')) {
                $table->boolean('overlay_blocks_action')->default(false);
            }

            if (!Schema::hasColumn('ux_metrics', 'timeout_occurred')) {
                $table->boolean('timeout_occurred')->default(false);
            }

            if (!Schema::hasColumn('ux_metrics', 'crash_detected')) {
                $table->boolean('crash_detected')->default(false);
            }

            if (!Schema::hasColumn('ux_metrics', 'anr_detected')) {
                $table->boolean('anr_detected')->default(false);
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE friction_results MODIFY prediction_source ENUM('main_gagent', 'baseline', 'android_appium', 'manual') DEFAULT 'main_gagent'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE friction_results MODIFY prediction_source ENUM('main_gagent', 'baseline', 'manual') DEFAULT 'main_gagent'"
            );
        }

        Schema::table('ux_metrics', function (Blueprint $table) {
            foreach ([
                'device_type',
                'platform_name',
                'app_launch_time_ms',
                'screen_load_time_ms',
                'interaction_response_time_ms',
                'finish_time_ms',
                'overlay_blocks_action',
                'timeout_occurred',
                'crash_detected',
                'anr_detected',
            ] as $column) {
                if (Schema::hasColumn('ux_metrics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('test_runs', function (Blueprint $table) {
            foreach ([
                'platform',
                'target_type',
                'target_app_package',
                'target_app_activity',
                'apk_path',
                'device_name',
                'automation_driver',
                'appium_exit_code',
            ] as $column) {
                if (Schema::hasColumn('test_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
