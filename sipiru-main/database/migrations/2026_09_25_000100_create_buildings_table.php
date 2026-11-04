<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('code_normalized', 30);
            $table->string('name', 150);
            $table->string('name_normalized', 150);
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            // Kunci unik hanya berlaku untuk baris yang belum di-soft-delete.
            // Baris terhapus menghasilkan NULL sehingga tidak memblokir nama/kode yang sama.
            $table->string('name_unique_key', 150)->nullable()
                ->storedAs('(case when `deleted_at` is null then `name_normalized` else null end)');
            $table->string('code_unique_key', 30)->nullable()
                ->storedAs('(case when `deleted_at` is null then `code_normalized` else null end)');

            $table->unique('name_unique_key', 'buildings_name_normalized_unique');
            $table->unique('code_unique_key', 'buildings_code_normalized_unique');
            $table->index('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
