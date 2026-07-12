<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'reports',
            function (Blueprint $table) {
                $table
                    ->text('llm_summary')
                    ->nullable()
                    ->after('conclusion');

                $table
                    ->longText('llm_explanation')
                    ->nullable()
                    ->after('llm_summary');

                $table
                    ->json('llm_recommendations')
                    ->nullable()
                    ->after('llm_explanation');

                $table
                    ->text('llm_risk_reason')
                    ->nullable()
                    ->after('llm_recommendations');

                $table
                    ->string('llm_model_name')
                    ->nullable()
                    ->after('llm_risk_reason');

                $table
                    ->boolean('llm_used')
                    ->default(false)
                    ->after('llm_model_name');

                $table
                    ->timestamp('llm_generated_at')
                    ->nullable()
                    ->after('llm_used');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'reports',
            function (Blueprint $table) {
                $table->dropColumn([
                    'llm_summary',
                    'llm_explanation',
                    'llm_recommendations',
                    'llm_risk_reason',
                    'llm_model_name',
                    'llm_used',
                    'llm_generated_at',
                ]);
            }
        );
    }
};
