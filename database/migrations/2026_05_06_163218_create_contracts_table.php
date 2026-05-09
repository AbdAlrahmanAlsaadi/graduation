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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('contract_no')->unique();
            $table->string('title');

            $table->date('contract_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->decimal('contract_value', 15, 2);
            $table->string('currency')->default('USD');

            $table->enum('status', ['Draft', 'Active', 'Completed', 'Cancelled'])
                ->default('Draft');


                $table->string('company_signature');
                $table->string('owner_signature');
                $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
