<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duration_extension_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('work_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->unsignedInteger('requested_duration_days');

            $table->text('reason');

            $table->text('comment')
                ->nullable()
                ->comment('reviewer comment');

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['work_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duration_extension_requests');
    }
};
