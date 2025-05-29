<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('dispositif_biometriques')->onDelete('cascade');
            $table->integer('uid');
            $table->string('userid', 9);
            $table->string('name', 24);
            $table->string('password', 8);
            $table->integer('role')->default(0);
            $table->string('cardno', 10)->default('0');
            $table->timestamps();

            $table->unique(['device_id', 'uid']);
            $table->unique(['device_id', 'userid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_users');
    }
}; 