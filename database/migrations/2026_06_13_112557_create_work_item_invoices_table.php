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
        Schema::create('work_item_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('work_item_id')
                ->constrained('work_items')
                ->cascadeOnDelete();

            $table->string('supplier_name');

            $table->string('invoice_number')->unique();

            $table->date('invoice_date');

            $table->string('invoice_image')
                ->nullable();

            $table->decimal('total_amount', 15, 2);

            $table->text('notes')
                ->nullable();


            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_item_invoices');
    }
};
