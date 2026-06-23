<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_update_requests', function (Blueprint $table) {
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

            $table->string('type')
                ->default('progress')
                ->comment('progress or room');

            $table->json('payload');

            $table->text('comment')
                ->nullable()
                ->comment('rejection reason');

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['project_id', 'work_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_update_requests');
    }
};
