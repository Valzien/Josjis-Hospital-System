<?php

namespace App\Http\Middleware;

use App\Enums\PrescriptionStatus;
use App\Enums\QueueStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan antrean yang sedang diproses benar-benar milik dokter yang login.
 * Mencegah dokter lain membuka antrean pasien milik dokter lain.
 */
class EnsureQueueOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $queue = $request->route('queue');

        if ($queue === null) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->isDoctor()) {
            $doctor = $user->doctor;

            if ($doctor === null || $queue->doctor_id !== $doctor->id) {
                abort(403, 'Antrean ini bukan milik jadwal Anda.');
            }
        }

        return $next($request);
    }

    /**
     * Pastikan antrean masih bisa ditangani (belum selesai/batal).
     */
    public static function isProcessable(?QueueStatus $status): bool
    {
        return $status === QueueStatus::Waiting
            || $status === QueueStatus::Called
            || $status === QueueStatus::InExamination;
    }

    /**
     * Pastikan resep masih bisa diproses apoteker.
     */
    public static function isPrescriptionProcessable(?PrescriptionStatus $status): bool
    {
        return $status === PrescriptionStatus::Pending
            || $status === PrescriptionStatus::Processing
            || $status === PrescriptionStatus::Ready;
    }
}
