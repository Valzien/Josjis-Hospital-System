<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignId('icd10_code_id')->nullable()->constrained('icd10_codes')->nullOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->string('type', 20)->default('PRIMARY')->index();
            $table->text('notes')->nullable();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->timestamp('diagnosed_at')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['admission_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_diagnoses');
    }
};
