<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->enum('ville', ['yaoundé', 'douala']);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['nom', 'ville']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quartiers');
    }
};
