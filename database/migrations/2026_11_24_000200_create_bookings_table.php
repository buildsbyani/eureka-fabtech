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
            $table->uuid('uid')->unique();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Minutes after midnight, office time zone.
            $table->unsignedSmallInteger('start_min');
            $table->unsignedSmallInteger('end_min');
            $table->string('title');
            $table->foreignId('organizer_id')->constrained('users');
            $table->foreignId('booked_by_id')->constrained('users');
            $table->json('invitees')->nullable();
            $table->enum('status', ['active', 'cancelled', 'released'])->default('active');
            $table->uuid('series_id')->nullable()->index();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'date', 'status']);
            $table->index(['organizer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
