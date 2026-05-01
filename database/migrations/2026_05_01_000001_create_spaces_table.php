<?php

use App\Models\Space;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->enum('type', Space::TYPE_OPTIONS);
            $table->decimal('area', 10, 2);
            $table->enum('finish_type', Space::FINISH_TYPES);
            $table->enum('toilet_type', Space::TOILET_TYPES)->default('none');
            $table->boolean('has_ceiling_ceramic')->default(false);
            $table->decimal('ceiling_ceramic_area', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
