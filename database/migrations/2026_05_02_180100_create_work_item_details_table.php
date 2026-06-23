<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_item_id')
                ->constrained('work_items')
                ->cascadeOnDelete()
                ->comment('parent work item');

            $table->string('key')
                ->comment('detail key');

            $table->text('value')->nullable()

                ->comment('approved value');

            $table->string('unit')
                ->nullable()
                ->comment('unit of measure');

            $table->timestamps();

            $table->index([
                'work_item_id',
                'key'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_details');
    }
};
