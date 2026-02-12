<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            $table->foreignId('zone_livraison_id')
                ->nullable()
                ->after('ville')
                ->constrained('zone_livraisons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_livraison_id');
        });
    }
};
