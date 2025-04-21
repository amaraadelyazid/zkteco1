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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('pause')->default(false);
            $table->time('heure_debut_pause')->nullable();
            $table->time('heure_fin_pause')->nullable();
            $table->integer('duree_pause')->nullable()->comment('en minutes');
            $table->json('jours_travail')->comment("['lundi', 'mardi', ...]");
            $table->integer('tolerance_retard')->default(0)->comment('en minutes');
            $table->integer('depart_anticipe')->default(0)->comment('en minutes');
            $table->integer('duree_min_presence')->comment('en minutes');
            $table->boolean('is_decalable')->default(false)->comment('permet les horaires flexibles ou non');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
