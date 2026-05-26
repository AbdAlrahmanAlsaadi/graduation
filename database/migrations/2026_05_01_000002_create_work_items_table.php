<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->comment('parent project');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('work_items')
                ->nullOnDelete()
                ->comment('optional parent work item');
            $table->string('name')->comment('work item name');
            $table->enum('quality_level', ['basic', 'good', 'premium', 'custom'])
                ->default('basic')
                ->comment('basic|good|premium|custom');
            $table->unsignedInteger('sort_order')->comment('sort order within project');
            $table->unsignedInteger('duration_days')
                ->default(10)
                ->comment('estimated duration in days');
            $table->boolean('is_default')
                ->default(false)
                ->comment('seeded default item');
            $table->boolean('is_active')
                ->default(true)
                ->comment('visibility flag');
            $table->boolean('is_custom')
                ->default(false)
                ->comment('custom user item');
            $table->enum('status', ['planned', 'ongoing', 'completed'])
                ->default('planned')
                ->comment('planned|ongoing|completed');
            $table->decimal('weight', 8, 2)->default(1)->comment('aggregation weight');
            $table->time('started_at')->nullable();
            $table->time('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
