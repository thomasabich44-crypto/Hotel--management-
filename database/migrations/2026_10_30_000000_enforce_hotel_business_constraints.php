<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['reservation_id']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('reservation_id')->references('id')->on('reservations')->restrictOnDelete();
        });

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->addCheck('users', 'users_role_valid', "role IN ('administrator', 'receptionist')");
        $this->addCheck('room_types', 'room_types_positive_base_rate', 'base_rate > 0');
        $this->addCheck('reservation_rooms', 'reservation_rooms_positive_nightly_rate', 'nightly_rate > 0');
        $this->addCheck('services', 'services_nonnegative_price', 'price >= 0');
        $this->addCheck('reservation_services', 'reservation_services_positive_quantity', 'quantity > 0');
        $this->addCheck('reservation_services', 'reservation_services_nonnegative_price', 'unit_price >= 0');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['reservation_id']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
        });

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropCheck('reservation_services', 'reservation_services_nonnegative_price');
        $this->dropCheck('reservation_services', 'reservation_services_positive_quantity');
        $this->dropCheck('services', 'services_nonnegative_price');
        $this->dropCheck('reservation_rooms', 'reservation_rooms_positive_nightly_rate');
        $this->dropCheck('room_types', 'room_types_positive_base_rate');
        $this->dropCheck('users', 'users_role_valid');
    }

    private function addCheck(string $table, string $name, string $expression): void
    {
        if (! $this->checkExists($table, $name)) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    private function dropCheck(string $table, string $name): void
    {
        if ($this->checkExists($table, $name)) {
            DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
        }
    }

    private function checkExists(string $table, string $name): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $name)
            ->where('constraint_type', 'CHECK')
            ->exists();
    }
};
