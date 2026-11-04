<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Kolom untuk menyimpan jenis kegiatan ('usk' atau 'non_usk')
            $table->string('activity_type', 20)->default('non_usk')->after('status');
            
            // Kolom untuk menyimpan harga final (0 jika USK, atau mengikuti harga ruangan jika Non-USK)
            $table->decimal('final_price', 15, 2)->default(0)->after('activity_type');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['activity_type', 'final_price']);
        });
    }
};