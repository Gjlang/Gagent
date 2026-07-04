<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('target_type', [
                'dummy_website',
                'web_application',
                'android_application',
            ])->default('dummy_website');
            $table->string('target_url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('run_code')->unique();
            $table->string('flow_type')->nullable();
            $table->string('scenario_type')->nullable();
            $table->string('viewport_type')->nullable();
            $table->string('page_url')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ux_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();

            $table->string('flow_type')->nullable();
            $table->string('scenario_type')->nullable();
            $table->string('viewport_type')->nullable();

            $table->boolean('task_completed')->default(false);
            $table->boolean('task_failed')->default(false);

            $table->double('completion_time')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('scroll_count')->default(0);
            $table->integer('keyboard_count')->default(0);
            $table->integer('retry_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('failed_clicks')->default(0);
            $table->integer('unnecessary_clicks')->default(0);

            $table->double('path_deviation_score')->default(0);
            $table->double('page_load_time_ms')->default(0);
            $table->double('dom_content_loaded_ms')->default(0);
            $table->double('time_to_first_byte_ms')->default(0);
            $table->double('feedback_delay_ms')->default(0);
            $table->double('interaction_to_next_paint_ms')->default(0);
            $table->double('cumulative_layout_shift')->default(0);

            $table->boolean('error_message_present')->default(false);
            $table->integer('error_message_clarity')->default(0);
            $table->boolean('popup_detected')->default(false);
            $table->boolean('cookie_banner_detected')->default(false);
            $table->boolean('overlay_blocks_cta')->default(false);

            $table->timestamps();

            $table->unique('test_run_id');
        });

        Schema::create('friction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();

            $table->string('model_name')->nullable();
            $table->string('model_type')->nullable();
            $table->enum('prediction_source', [
                'main_gagent',
                'baseline',
                'manual',
            ])->default('main_gagent');

            $table->enum('friction_level', [
                'Low',
                'Medium',
                'High',
            ])->nullable();

            $table->double('confidence_score')->nullable();
            $table->json('class_probabilities')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('input_features')->nullable();
            $table->boolean('is_final')->default(false);

            $table->timestamps();

            $table->index(['test_run_id', 'prediction_source']);
            $table->index(['friction_level']);
        });

        Schema::create('interaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->nullable();
            $table->string('event_label')->nullable();
            $table->string('event_value')->nullable();
            $table->double('event_time')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('label')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique('test_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('screenshots');
        Schema::dropIfExists('interaction_logs');
        Schema::dropIfExists('friction_results');
        Schema::dropIfExists('ux_metrics');
        Schema::dropIfExists('test_runs');
        Schema::dropIfExists('projects');
    }
};
