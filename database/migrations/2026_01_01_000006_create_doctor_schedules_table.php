<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedTinyInteger('day')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 30)->nullable();
            $table->unsignedSmallInteger('quota')->default(30);
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['doctor_id', 'day', 'start_time'], 'doctor_schedules_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
