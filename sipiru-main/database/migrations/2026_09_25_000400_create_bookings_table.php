<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('period_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Token acak untuk QR verifikasi publik (bukan ID database).
            $table->string('verification_token', 64)->nullable()->unique();
            $table->timestamp('reminder_day_sent_at')->nullable();
            $table->timestamp('reminder_soon_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'status', 'start_at', 'end_at'], 'bookings_availability_index');
            $table->index(['user_id', 'status']);
            $table->index('start_at');
        });

        // Nomor urut harian untuk booking_number (PMJ-YYYYMMDD-NNNN)
        Schema::create('booking_counters', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_counters');
        Schema::dropIfExists('bookings');
    }
};
