<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_engineers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->comment('parent project');
            // TODO: confirm delete behavior for user assignments.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('assigned engineer');
            $table->string('role')->comment('assignment role');
            $table->timestamp('assigned_at')->nullable()->comment('assignment timestamp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_engineers');
    }
};
