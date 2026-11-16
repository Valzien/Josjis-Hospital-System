<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_visits', function (Blueprint $table) {
            $table->decimal('temperature', 4, 1)->nullable()->after('notes');
            $table->decimal('blood_pressure', 5, 1)->nullable()->after('temperature');
            $table->unsignedSmallInteger('pulse')->nullable()->after('blood_pressure');
            $table->unsignedSmallInteger('respiratory_rate')->nullable()->after('pulse');
            $table->unsignedTinyInteger('oxygen_saturation')->nullable()->after('respiratory_rate');
            $table->decimal('weight', 5, 1)->nullable()->after('oxygen_saturation');
        });
    }

    public function down(): void
    {
        Schema::table('admission_visits', function (Blueprint $table) {
            $table->dropColumn([
                'temperature', 'blood_pressure', 'pulse',
                'respiratory_rate', 'oxygen_saturation', 'weight',
            ]);
        });
    }
};
