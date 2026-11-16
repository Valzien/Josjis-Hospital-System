<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slot janji temu sebelumnya dikunci dengan unique index pada
     * (doctor_id, appointment_date, appointment_time). Kunci itu tetap menahan
     * slot walau janji temu sudah dibatalkan, sehingga pasien tidak bisa
     * memesan ulang jam yang sama.
     *
     * Solusinya: kolom slot_key nullable yang diisi saat booking aktif dan
     * dikosongkan (NULL) ketika dibatalkan / tidak hadir. MySQL maupun SQLite
     * mengizinkan banyak NULL pada unique index.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('slot_key', 120)->nullable()->after('appointment_time');
        });

        // MySQL memakai unique (doctor_id, appointment_date, appointment_time)
        // sebagai index foreign key doctor_id. Tambahkan index khusus agar
        // unique tersebut boleh dilepas tanpa melanggar constraint.
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('doctor_id', 'appointments_doctor_id_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_unique_slot');
        });

        DB::table('appointments')
            ->select(['id', 'doctor_id', 'appointment_date', 'appointment_time', 'status'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $released = in_array($row->status, ['CANCELLED', 'NO_SHOW'], true);

                    DB::table('appointments')->where('id', $row->id)->update([
                        'slot_key' => $released
                            ? null
                            : $row->doctor_id.'|'.$row->appointment_date.'|'.$row->appointment_time,
                    ]);
                }
            });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('slot_key', 'appointments_slot_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_slot_key_unique');
            $table->dropColumn('slot_key');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique(['doctor_id', 'appointment_date', 'appointment_time'], 'appointments_unique_slot');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_doctor_id_index');
        });
    }
};
