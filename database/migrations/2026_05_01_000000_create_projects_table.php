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
            $table->foreignId('project_manager_id')
                ->after('height')
                ->constrained('users');
            $table->foreignId('assistant_engineer_id')
                ->after('project_manager_id')
                ->constrained('users');
            $table->foreignId('owner_id')
                ->nullable()
                ->after('assistant_engineer_id')
                ->constrained('users');
            $table->string('location');
            $table->decimal('total_area', 10, 2);
            $table->decimal('height', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
