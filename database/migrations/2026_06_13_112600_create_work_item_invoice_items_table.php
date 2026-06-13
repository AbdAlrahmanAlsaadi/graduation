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
        Schema::create('work_item_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                ->constrained('work_item_invoices')
                ->cascadeOnDelete();

            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materials')
                ->nullOnDelete();

            $table->string('material_name_snapshot');

            $table->decimal('quantity', 15, 2);

            $table->string('unit');

            $table->decimal('unit_price', 15, 2);

            $table->decimal('total_price', 15, 2);

            $table->text('notes')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_item_invoice_items');
    }
};
