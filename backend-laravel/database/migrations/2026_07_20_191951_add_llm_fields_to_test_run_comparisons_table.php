<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'test_run_comparisons',
            function (Blueprint $table) {
                $table->longText('llm_summary')
                    ->nullable();

                $table->longText('llm_assessment')
                    ->nullable();

                $table->json('llm_improvements')
                    ->nullable();

                $table->json('llm_regressions')
                    ->nullable();

                $table->json('llm_next_actions')
                    ->nullable();

                $table->string('llm_provider', 100)
                    ->nullable();

                $table->string('llm_model', 100)
                    ->nullable();

                $table->text('llm_error')
                    ->nullable();

                $table->timestamp('llm_generated_at')
                    ->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'test_run_comparisons',
            function (Blueprint $table) {
                $table->dropColumn([
                    'llm_summary',
                    'llm_assessment',
                    'llm_improvements',
                    'llm_regressions',
                    'llm_next_actions',
                    'llm_provider',
                    'llm_model',
                    'llm_error',
                    'llm_generated_at',
                ]);
            }
        );
    }
};
