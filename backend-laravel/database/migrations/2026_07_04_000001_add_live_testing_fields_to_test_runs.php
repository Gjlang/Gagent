<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('test_runs', 'target_url')) {
                $table->text('target_url')->nullable()->after('page_url');
            }

            if (!Schema::hasColumn('test_runs', 'network_condition')) {
                $table->string('network_condition')->nullable()->after('viewport_type');
            }

            if (!Schema::hasColumn('test_runs', 'run_mode')) {
                $table->string('run_mode')->default('demo')->after('network_condition');
            }

            if (!Schema::hasColumn('test_runs', 'max_duration_seconds')) {
                $table->integer('max_duration_seconds')->default(60)->after('run_mode');
            }

            if (!Schema::hasColumn('test_runs', 'duration_seconds')) {
                $table->double('duration_seconds')->nullable()->after('completed_at');
            }

            if (!Schema::hasColumn('test_runs', 'playwright_exit_code')) {
                $table->integer('playwright_exit_code')->nullable()->after('duration_seconds');
            }

            if (!Schema::hasColumn('test_runs', 'error_message')) {
                $table->text('error_message')->nullable()->after('playwright_exit_code');
            }

            if (!Schema::hasColumn('test_runs', 'raw_metrics_path')) {
                $table->text('raw_metrics_path')->nullable()->after('error_message');
            }

            if (!Schema::hasColumn('test_runs', 'report_path')) {
                $table->text('report_path')->nullable()->after('raw_metrics_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            $columns = [
                'target_url',
                'network_condition',
                'run_mode',
                'max_duration_seconds',
                'duration_seconds',
                'playwright_exit_code',
                'error_message',
                'raw_metrics_path',
                'report_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('test_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
