<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom_complet');
            $table->string('email')->unique()->nullable();
            $table->string('telephone', 20)->unique();
            $table->string('mot_de_passe');
            $table->enum('role', ['client', 'admin', 'livreur'])->default('client');
            $table->string('photo_profil')->nullable();
            $table->text('adresse_principale')->nullable();
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(['role', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
