<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('message');
            $table->enum('type', ['commande', 'livraison', 'promotion', 'compte', 'systeme']);
            $table->enum('canal', ['app', 'email', 'sms', 'push']);
            $table->boolean('est_lu')->default(false);
            $table->string('url_action')->nullable();
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_lecture')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'est_lu', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
