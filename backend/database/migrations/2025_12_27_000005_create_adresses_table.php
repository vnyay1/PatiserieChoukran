<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('libelle', 100)->nullable();
            $table->string('quartier');
            $table->string('ville');
            $table->string('telephone_contact', 20);
            $table->text('point_repere')->nullable();
            $table->text('complement_adresse')->nullable();
            $table->boolean('est_principale')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'est_principale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};
