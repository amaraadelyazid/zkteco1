<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['admin', 'grh', 'employe']);
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable(); // Added for employee's or GRH's name
            $table->string('prenom')->nullable(); // Added for employee's or GRH's first name
            $table->date('date');
            $table->dateTime('check_in')->nullable();
            $table->enum('etat_check_in', ['present', 'retard', 'absent'])->nullable();
            $table->dateTime('check_out')->nullable();
            $table->enum('etat_check_out', ['present', 'retard', 'absent'])->nullable();
            $table->time('heures_travaillees')->nullable();
            $table->enum('anomalie_type', ['unique_pointage', 'absent', 'incomplet', 'hors_shift'])->nullable();
            $table->boolean('anomalie_resolue')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};

