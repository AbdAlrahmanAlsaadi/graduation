<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_material_templates', function (Blueprint $table) {
            $table->id();
            $table->string('work_item_type')->comment('work item type identifier');
            $table->string('material_name')->comment('material name');
            $table->string('unit')->comment('unit of measure');
            $table->decimal('default_qty', 10, 2)
                ->nullable()
                ->comment('default quantity');
            $table->string('category')->nullable()->comment('material category');
            $table->timestamps();

            $table->index('work_item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_material_templates');
    }
};
