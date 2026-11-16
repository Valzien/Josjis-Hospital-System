<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->text('discharge_condition')->nullable()->after('discharge_notes');
            $table->text('discharge_plan')->nullable()->after('discharge_condition');
            $table->date('follow_up_date')->nullable()->after('discharge_plan');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['discharge_condition', 'discharge_plan', 'follow_up_date']);
        });
    }
};
