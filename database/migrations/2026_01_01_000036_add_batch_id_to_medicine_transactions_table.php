<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_transactions', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->after('medicine_id')
                ->constrained('medicine_batches')
                ->nullOnDelete();
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_transactions', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
