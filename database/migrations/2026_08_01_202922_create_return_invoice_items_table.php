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
        Schema::create('return_invoice_items', function (Blueprint $table) {
            $table->id();
           
            $table->foreignId('return_invoice_id')
                ->constrained('return_invoices')
                ->cascadeOnDelete();

            // 2. المادة المرتجعة (قد تكون معدات أو مواد، اختياري لو حذفت المادة)
            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materials')
                ->nullOnDelete();

            // 3. اسم المادة (لقطة لحفظ الاسم حتى لو حُذفت المادة لاحقاً)
            $table->string('material_name_snapshot');

            // 4. نوع العنصر (مادة، معدة، أو غيرها) عشان نعرف شو المرتجع بالضبط
            $table->enum('item_type', ['material', 'equipment', 'other'])
                ->default('material');

            // 5. الكميات والأسعار
            $table->decimal('quantity', 15, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);

            // 6. سبب المرتجع (مثلاً: تالف، زيادة عن الحاجة، غير مطابق للمواصفات... إلخ)
            $table->text('reason')->nullable();

            // 7. ملاحظات إضافية
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_invoice_items');
    }
};
