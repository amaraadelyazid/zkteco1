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
        Schema::create('primes', function (Blueprint $table) {
            $table->id();
            $table->string('user_type'); // App\Models\Employe ou Grh
            $table->unsignedBigInteger('user_id');
            $table->string('mois'); // format YYYY-MM
            $table->float('montant');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('primes');
    }
};
