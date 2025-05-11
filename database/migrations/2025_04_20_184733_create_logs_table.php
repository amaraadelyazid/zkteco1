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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('level')->default('info'); // info, warning, error
            $table->string('action'); // import_users, import_attendance, sync_status, etc.
            $table->text('message');
            $table->json('context')->nullable(); // Pour stocker des données supplémentaires
            $table->string('device_ip')->nullable(); // IP du dispositif ZKTeco
            $table->string('device_port')->nullable(); // Port du dispositif ZKTeco
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

