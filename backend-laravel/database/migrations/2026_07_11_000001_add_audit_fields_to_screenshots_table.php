<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshots', function (Blueprint $table) {
            $table
                ->string('flow_key')
                ->nullable()
                ->after('label')
                ->index();

            $table
                ->string('friction_level')
                ->nullable()
                ->after('flow_key');

            $table
                ->double('confidence_score')
                ->nullable()
                ->after('friction_level');
        });
    }

    public function down(): void
    {
        Schema::table('screenshots', function (Blueprint $table) {
            $table->dropIndex(['flow_key']);

            $table->dropColumn([
                'flow_key',
                'friction_level',
                'confidence_score',
            ]);
        });
    }
};
