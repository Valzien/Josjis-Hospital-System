<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
            $table->string('batch_number', 80);
            $table->unsignedInteger('quantity')->default(0);
            $table->date('expiry_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['medicine_id', 'batch_number']);
            $table->index(['expiry_date', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
