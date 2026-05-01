<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()

                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('type');
            $table->string('identifier_no')->unique();

            $table->enum('status', ['Available', 'Maintenance', 'Booked'])
                ->default('Available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
