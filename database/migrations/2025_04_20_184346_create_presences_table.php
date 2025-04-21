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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained()->onDelete('cascade');
            $table->dateTime('timestamp');
            $table->string('type'); // entrée, sortie
            $table->string('methode'); // biométrique, manuel, etc.
            $table->boolean('is_anomalie')->default(false);
            $table->enum('etat', ['present', 'absent', 'retard']);
            $table->date('jour');
            $table->foreignId('dispositif_id')->nullable()->constrained('dispositif_biometriques')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};

