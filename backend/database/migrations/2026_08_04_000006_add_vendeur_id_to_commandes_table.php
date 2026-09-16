<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('commandes', 'vendeur_id')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('vendeur_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('commandes', 'vendeur_id')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendeur_id');
        });
    }
};
