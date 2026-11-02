<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('receptionist')->index();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        Schema::create('reservation_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
            $table->unique(['reservation_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_services');
        Schema::dropIfExists('services');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('reservations')->where('status', 'pending')->update(['status' => 'confirmed']);
            DB::statement("ALTER TABLE reservations MODIFY status ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'confirmed'");
        }
    }
};
