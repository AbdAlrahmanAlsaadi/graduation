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
                ->cascadeOnDelete()
                ->comment('parent project');
            $table->enum('type', Space::TYPE_OPTIONS)
                ->comment('space type');
            $table->decimal('wall_area', 10, 2)->comment('wall surface area');
            $table->decimal('floor_area', 10, 2)->comment('floor surface area');
            $table->enum('wall_finish_type', Space::FINISH_TYPES)
                ->comment('wall finish type');
            $table->enum('ceiling_finish_type', Space::FINISH_TYPES)
                ->default('none')
                ->comment('ceiling finish type');
            $table->enum('toilet_type', Space::TOILET_TYPES)
                ->default('none')
                ->comment('toilet fixture type');
            $table->decimal('ceiling_ceramic_area', 10, 2)
                ->nullable()
                ->comment('ceiling ceramic area when applicable');
            $table->boolean('is_balcony_floor_tiled')
                ->default(false)
                ->comment('balcony floor tiled flag');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
