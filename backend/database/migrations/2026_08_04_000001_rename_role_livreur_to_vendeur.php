<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur','vendeur') NOT NULL DEFAULT 'client'");
            DB::table('users')->where('role', 'livreur')->update(['role' => 'vendeur']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','vendeur') NOT NULL DEFAULT 'client'");

            return;
        }

        // Autres drivers (SQLite des tests) : le schema builder reconstruit la colonne.
        $this->changeRoleEnum(['client', 'admin', 'livreur', 'vendeur']);
        DB::table('users')->where('role', 'livreur')->update(['role' => 'vendeur']);
        $this->changeRoleEnum(['client', 'admin', 'vendeur']);
    }

    public function down(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur','vendeur') NOT NULL DEFAULT 'client'");
            DB::table('users')->where('role', 'vendeur')->update(['role' => 'livreur']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur') NOT NULL DEFAULT 'client'");

            return;
        }

        $this->changeRoleEnum(['client', 'admin', 'livreur', 'vendeur']);
        DB::table('users')->where('role', 'vendeur')->update(['role' => 'livreur']);
        $this->changeRoleEnum(['client', 'admin', 'livreur']);
    }

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function changeRoleEnum(array $roles): void
    {
        Schema::table('users', function (Blueprint $table) use ($roles) {
            $table->enum('role', $roles)->default('client')->change();
        });
    }
};
