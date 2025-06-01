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
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prenom');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('adresse');
            $table->string('Numero_telephone');
            $table->rememberToken();
            $table->integer('biometric_id')->unique();
            $table->float('salaire');
            $table->string('poste');
            $table->foreignId('departement_id')->constrained()->onDelete('restrict')->nullable();
            $table->foreignId('shift_id')->constrained()->onDelete('restrict');
            $table->timestamps();
            $table->boolean("is_grh")->default(false);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('admin_id')->nullable()->index();
            $table->foreignId('grh_id')->nullable()->index();
            $table->foreignId('employe_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
        Schema::dropIfExists('sessions');
    }
};
