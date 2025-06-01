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
        Schema::create('fiche_de_paies', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['grh', 'employe']);
            $table->unsignedBigInteger('user_id');
            $table->string('mois');
            $table->float('montant');
            $table->float('prime')->default(0);
            $table->float('avance')->default(0);
            $table->float('heures_sup')->default(0);
            $table->decimal('taux_horaire_sup', 8, 2)->nullable();
            $table->decimal('montant_heures_sup', 10, 2)->nullable();
            $table->enum('status', ['en_attente', 'paye'])->default('en_attente');
            $table->dateTime('date_generation')->useCurrent();;
            $table->timestamps();

            $table->index(['user_type', 'user_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_de_paies');
    }
};

