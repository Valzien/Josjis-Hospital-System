<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('status', 20)->default('AVAILABLE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
