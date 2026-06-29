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
        Schema::create('ai_visualizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_image_id')
                ->constrained('project_images')
                ->cascadeOnDelete();

            

            $table->json('reference_images')->nullable();

            $table->string('generated_image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_visualizations');
    }
};
