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
        Schema::create('return_invoices', function (Blueprint $table) {
            $table->id();
            // 1. الربط بالمشروع (إجباري)
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            // 2. الربط ببند العمل (اختياري، لأن المرتجع ممكن يكون عام للمشروع)
            $table->foreignId('work_item_id')
                ->nullable()
                ->constrained('work_items')
                ->nullOnDelete();

            // 3. بيانات الفاتورة الأساسية
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->string('supplier_name');

            // 4. معلومات خاصة بالمرتجع
            $table->enum('return_type', ['material', 'equipment', 'subcontractor', 'other'])
                ->default('material');
            $table->text('description')->nullable();

            // 5. المرفقات (صورة أو ملف)
            $table->string('attachment_path')->nullable();

            // 6. من قام بالإنشاء
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
        Schema::dropIfExists('return_invoices');
    }
};
