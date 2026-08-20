<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur','vendeur') NOT NULL DEFAULT 'client'");
        DB::table('users')->where('role', 'livreur')->update(['role' => 'vendeur']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','vendeur') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur','vendeur') NOT NULL DEFAULT 'client'");
        DB::table('users')->where('role', 'vendeur')->update(['role' => 'livreur']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('client','admin','livreur') NOT NULL DEFAULT 'client'");
    }
};
