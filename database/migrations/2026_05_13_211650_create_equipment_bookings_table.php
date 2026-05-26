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
        Schema::create('equipment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->cascadeOnDelete();

            $table->foreignId('work_item_id')
                ->constrained('work_items')
                ->cascadeOnDelete();

            $table->foreignId('booked_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('start_date');

            $table->date('end_date')->nullable();

            $table->enum('status', [
                'active',
                'completed',
                'cancelled',
            ])->default('active');
            $table->text('notes');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_bookings');
    }
};
