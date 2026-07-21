<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_run_comparisons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('before_test_run_id')
                ->constrained('test_runs')
                ->cascadeOnDelete();

            $table->foreignId('after_test_run_id')
                ->constrained('test_runs')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'before_test_run_id',
                    'after_test_run_id',
                ],
                'unique_test_run_comparison'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_run_comparisons');
    }
};
