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
                ->constrained('users');
            $table->foreignId('assistant_engineer_id')
                ->constrained('users');
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users');
            $table->string('location');
            $table->string('latitude');
            $table->string('longitude');
            $table->decimal('total_area', 10, 2);
            $table->decimal('height', 10, 2);
            $table->enum('status', ['Planned', 'Ongoing', 'Completed'])
                ->default('Planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
