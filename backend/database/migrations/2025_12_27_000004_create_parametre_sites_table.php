<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre_sites', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->text('valeur')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json']);
            $table->text('description')->nullable();
            $table->string('groupe', 100)->nullable();
            $table->timestamps();
            
            $table->index('groupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre_sites');
    }
};
