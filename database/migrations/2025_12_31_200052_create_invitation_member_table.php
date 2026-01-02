<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Informations du destinataire
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('role')->default('manager');

            // Token et sécurité
            $table->string('token', 100)->unique();

            // Message personnalisé
            $table->text('message')->nullable();

            // Relations
            $table->uuid('invited_by_id');
            $table->foreign('invited_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Statut
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();
            $table->softDeletes();

            // Index pour les recherches courantes
            $table->index(['email', 'accepted_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
