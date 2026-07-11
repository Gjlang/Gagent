<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('test_runs', 'test_mode')) {
                $table->string('test_mode')
                    ->nullable()
                    ->after('run_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('test_runs', function (Blueprint $table) {
            if (Schema::hasColumn('test_runs', 'test_mode')) {
                $table->dropColumn('test_mode');
            }
        });
    }
};
