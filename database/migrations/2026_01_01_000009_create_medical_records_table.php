<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number', 30)->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('queue_id')->nullable()->unique()->constrained('queues')->nullOnDelete();
            $table->text('complaint');
            $table->text('examination_result')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('blood_pressure', 5, 1)->nullable();
            $table->unsignedSmallInteger('weight')->nullable();
            $table->decimal('height', 4, 1)->nullable();
            $table->timestamp('examined_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
