<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();

            // Bernilai 1 hanya untuk periode aktif, NULL untuk lainnya.
            // UNIQUE di kolom ini menjamin di level database hanya ada SATU periode aktif.
            $table->unsignedTinyInteger('active_flag')->nullable()
                ->storedAs("(case when `status` = 'active' then 1 else null end)");
            $table->unique('active_flag', 'periods_single_active_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
