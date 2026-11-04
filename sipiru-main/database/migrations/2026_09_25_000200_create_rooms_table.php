<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->restrictOnDelete();
            $table->string('code', 40);
            $table->string('code_normalized', 40);
            $table->string('name', 150);
            $table->string('name_normalized', 150);
            $table->string('floor', 20)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->text('description')->nullable();
            $table->string('public_token', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->string('name_unique_key', 150)->nullable()
                ->storedAs('(case when `deleted_at` is null then `name_normalized` else null end)');
            $table->string('code_unique_key', 40)->nullable()
                ->storedAs('(case when `deleted_at` is null then `code_normalized` else null end)');

            // Ruang unik DI DALAM gedungnya.
            $table->unique(['building_id', 'name_unique_key'], 'rooms_building_name_normalized_unique');
            $table->unique(['building_id', 'code_unique_key'], 'rooms_building_code_normalized_unique');
            $table->index('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
