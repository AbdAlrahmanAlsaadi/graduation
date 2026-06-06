<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_materials', function (Blueprint $table) {
            $table->id();
            $table->string('work_item_name');
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['work_item_name', 'material_id']);
            $table->index('work_item_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_materials');
    }
};
