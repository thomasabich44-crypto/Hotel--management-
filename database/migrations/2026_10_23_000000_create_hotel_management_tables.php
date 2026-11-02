<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('capacity');
            $table->decimal('base_rate', 10, 2);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->string('room_number', 12)->unique();
            $table->unsignedSmallInteger('floor')->default(1);
            $table->enum('status', ['available', 'maintenance'])->default('available');
            $table->timestamps();
            $table->index(['room_type_id', 'status']);
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 190)->unique();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->string('confirmation_code', 16)->unique();
            $table->date('check_in');
            $table->date('check_out');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'check_in', 'check_out']);
        });

        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->decimal('nightly_rate', 10, 2);
            $table->timestamps();
            $table->unique(['reservation_id', 'room_id']);
            $table->index('room_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'card', 'bank_transfer']);
            $table->enum('status', ['paid', 'refunded'])->default('paid');
            $table->dateTime('paid_at');
            $table->timestamps();
            $table->index(['reservation_id', 'paid_at']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_valid_dates CHECK (check_out > check_in)');
            DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_positive_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservation_rooms');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
    }
};
