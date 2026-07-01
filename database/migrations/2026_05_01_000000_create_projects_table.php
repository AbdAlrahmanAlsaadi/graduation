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
            $table->string('name')->comment('project name');
            $table->string('location')->comment('project location');
            $table->string('latitude')->comment('latitude as string');
            $table->string('longitude')->comment('longitude as string');
            $table->decimal('apartment_area', 10, 2)->comment('apartment area');
            $table->decimal('height', 8, 2)->comment('floor-to-ceiling height');
            $table->enum('status', ['planned', 'ongoing', 'completed'])
                ->default('planned')
                ->comment('planned|ongoing|completed');

            // TODO: confirm delete behavior for user assignments.
            $table->foreignId('project_manager_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('assigned project manager');
            $table->foreignId('assistant_engineer_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('assigned assistant engineer');
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('project owner');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('created by user');
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('last updated by user');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
