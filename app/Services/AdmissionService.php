<?php

namespace App\Services;

use App\Enums\AdmissionChargeType;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DiagnosisType;
use App\Models\Admission;
use App\Models\AdmissionCharge;
use App\Models\AdmissionDiagnosis;
use App\Models\AdmissionVisit;
use App\Models\Bed;
use App\Models\Icd10Code;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alur rawat inap: pendaftaran, pindah tempat tidur, visite, biaya, dan pulang.
 *
 * Setiap perubahan tempat tidur dilakukan dalam transaksi dengan penguncian
 * baris agar dua pasien tidak dapat menempati tempat tidur yang sama.
 */
class AdmissionService
{
    public function __construct(
        private readonly NumberGenerator $numbers,
        private readonly BillingService $billing,
    ) {}

    /**
     * @param  array{patient_id:int, doctor_id:int, bed_id:int, admitted_at?:mixed, complaint?:?string, diagnosis?:?string, notes?:?string}  $data
     *
     * @throws RuntimeException
     */
    public function admit(array $data, ?int $userId = null): Admission
    {
        return DB::transaction(function () use ($data, $userId) {
            /** @var Bed $bed */
            $bed = Bed::query()->with('room')->lockForUpdate()->findOrFail($data['bed_id']);

            if (! $bed->isAvailable()) {
                throw new RuntimeException("Tempat tidur {$bed->label()} tidak tersedia.");
            }

            $alreadyAdmitted = Admission::query()
                ->where('patient_id', $data['patient_id'])
                ->where('status', AdmissionStatus::Active->value)
                ->exists();

            if ($alreadyAdmitted) {
                throw new RuntimeException('Pasien ini masih memiliki rawat inap yang aktif.');
            }

            $admission = Admission::create([
                'code' => $this->numbers->admissionCode(),
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'room_id' => $bed->room_id,
                'bed_id' => $bed->id,
                'status' => AdmissionStatus::Active->value,
                'admitted_at' => $data['admitted_at'] ?? now(),
                'complaint' => $data['complaint'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $bed->update(['status' => BedStatus::Occupied->value]);

            $admission->movements()->create([
                'from_bed_id' => null,
                'to_bed_id' => $bed->id,
                'reason' => 'Masuk rawat inap',
                'moved_at' => $admission->admitted_at,
                'created_by' => $userId,
            ]);

            return $admission->load(['patient', 'doctor', 'room', 'bed']);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function transfer(Admission $admission, int $bedId, ?string $reason = null, ?int $userId = null): Admission
    {
        return DB::transaction(function () use ($admission, $bedId, $reason, $userId) {
            /** @var Admission $locked */
            $locked = Admission::query()->lockForUpdate()->findOrFail($admission->id);

            if (! $locked->isActive()) {
                throw new RuntimeException('Rawat inap ini sudah tidak aktif.');
            }

            if ((int) $locked->bed_id === $bedId) {
                throw new RuntimeException('Pasien sudah berada di tempat tidur tersebut.');
            }

            /** @var Bed $bed */
            $bed = Bed::query()->with('room')->lockForUpdate()->findOrFail($bedId);

            if (! $bed->isAvailable()) {
                throw new RuntimeException("Tempat tidur {$bed->label()} tidak tersedia.");
            }

            $fromBedId = $locked->bed_id;

            if ($fromBedId !== null) {
                Bed::query()->whereKey($fromBedId)->update(['status' => BedStatus::Available->value]);
            }

            $bed->update(['status' => BedStatus::Occupied->value]);

            $locked->update([
                'room_id' => $bed->room_id,
                'bed_id' => $bed->id,
            ]);

            $locked->movements()->create([
                'from_bed_id' => $fromBedId,
                'to_bed_id' => $bed->id,
                'reason' => $reason ?: 'Pindah tempat tidur',
                'moved_at' => now(),
                'created_by' => $userId,
            ]);

            return $locked->refresh()->load(['patient', 'doctor', 'room', 'bed']);
        });
    }

    /**
     * @param  array{notes:string, visited_at?:mixed, doctor_id?:?int, temperature?:mixed, blood_pressure?:mixed, pulse?:mixed, respiratory_rate?:mixed, oxygen_saturation?:mixed, weight?:mixed}  $data
     *
     * @throws RuntimeException
     */
    public function recordVisit(Admission $admission, array $data, ?int $userId = null): AdmissionVisit
    {
        if (! $admission->isActive()) {
            throw new RuntimeException('Visite hanya dapat dicatat pada rawat inap yang masih aktif.');
        }

        return $admission->visits()->create([
            'doctor_id' => $data['doctor_id'] ?? null,
            'visited_at' => $data['visited_at'] ?? now(),
            'notes' => $data['notes'],
            'temperature' => $data['temperature'] ?? null,
            'blood_pressure' => $data['blood_pressure'] ?? null,
            'pulse' => $data['pulse'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'oxygen_saturation' => $data['oxygen_saturation'] ?? null,
            'weight' => $data['weight'] ?? null,
            'created_by' => $userId,
        ]);
    }

    /**
     * Menambahkan diagnosis ICD-10. Hanya satu diagnosis utama yang disimpan;
     * diagnosis utama lama otomatis diturunkan menjadi diagnosis tambahan.
     *
     * @param  array{icd10_code_id:int, type:string, notes?:?string, doctor_id?:?int, diagnosed_at?:mixed}  $data
     *
     * @throws RuntimeException
     */
    public function addDiagnosis(Admission $admission, array $data, ?int $userId = null): AdmissionDiagnosis
    {
        return DB::transaction(function () use ($admission, $data, $userId) {
            /** @var Admission $locked */
            $locked = Admission::query()->lockForUpdate()->findOrFail($admission->id);

            if (! $locked->isActive()) {
                throw new RuntimeException('Diagnosis hanya dapat ditambahkan pada rawat inap yang masih aktif.');
            }

            $code = Icd10Code::query()->findOrFail($data['icd10_code_id']);

            if ($locked->diagnoses()->where('code', $code->code)->exists()) {
                throw new RuntimeException("Diagnosis {$code->code} sudah tercatat pada rawat inap ini.");
            }

            $type = DiagnosisType::tryFrom((string) $data['type']) ?? DiagnosisType::Secondary;

            if ($type === DiagnosisType::Primary) {
                AdmissionDiagnosis::query()
                    ->where('admission_id', $locked->id)
                    ->where('type', DiagnosisType::Primary->value)
                    ->update(['type' => DiagnosisType::Secondary->value]);
            }

            return $locked->diagnoses()->create([
                'icd10_code_id' => $code->id,
                'code' => $code->code,
                'name' => $code->name,
                'type' => $type->value,
                'notes' => $data['notes'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null,
                'diagnosed_at' => $data['diagnosed_at'] ?? now(),
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function removeDiagnosis(Admission $admission, AdmissionDiagnosis $diagnosis, ?int $userId = null): void
    {
        if (! $admission->isActive()) {
            throw new RuntimeException('Diagnosis hanya dapat dihapus pada rawat inap yang masih aktif.');
        }

        if ($diagnosis->admission_id !== $admission->id) {
            throw new RuntimeException('Diagnosis tidak termasuk dalam rawat inap ini.');
        }

        $diagnosis->delete();
    }

    /**
     * Menyimpan ringkasan pulang yang ditulis dokter.
     *
     * @param  array{discharge_condition?:?string, discharge_plan?:?string, follow_up_date?:mixed}  $data
     *
     * @throws RuntimeException
     */
    public function updateDischargeSummary(Admission $admission, array $data): Admission
    {
        if ($admission->status === AdmissionStatus::Cancelled) {
            throw new RuntimeException('Ringkasan pulang tidak dapat diisi pada rawat inap yang dibatalkan.');
        }

        $admission->update([
            'discharge_condition' => $data['discharge_condition'] ?? null,
            'discharge_plan' => $data['discharge_plan'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
        ]);

        return $admission->refresh();
    }

    /**
     * @throws RuntimeException
     */
    public function addServiceCharge(Admission $admission, Service $service, int $quantity = 1, ?int $userId = null): AdmissionCharge
    {
        if (! $admission->isActive()) {
            throw new RuntimeException('Tindakan hanya dapat ditambahkan pada rawat inap yang masih aktif.');
        }

        $quantity = max(1, $quantity);
        $price = (float) $service->price;

        return $admission->charges()->create([
            'type' => AdmissionChargeType::Service->value,
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => $price * $quantity,
            'charged_at' => now(),
            'created_by' => $userId,
        ]);
    }

    /**
     * Menyelesaikan rawat inap, menagihkan biaya kamar, lalu membuat invoice.
     *
     * @throws RuntimeException
     */
    public function discharge(
        Admission $admission,
        ?string $dischargeNotes = null,
        mixed $dischargedAt = null,
        ?int $userId = null,
    ): Admission {
        return DB::transaction(function () use ($admission, $dischargeNotes, $dischargedAt, $userId) {
            /** @var Admission $locked */
            $locked = Admission::query()
                ->with(['room', 'bed', 'charges'])
                ->lockForUpdate()
                ->findOrFail($admission->id);

            if (! $locked->isActive()) {
                throw new RuntimeException('Rawat inap ini sudah tidak aktif.');
            }

            $discharged = $dischargedAt ? Carbon::parse($dischargedAt) : now();

            if ($locked->room !== null && (float) $locked->room->rate_per_day > 0) {
                $hasRoomCharge = $locked->charges
                    ->contains(fn (AdmissionCharge $charge) => $charge->type === AdmissionChargeType::Room);

                if (! $hasRoomCharge) {
                    $days = $locked->dayCount($discharged);
                    $price = (float) $locked->room->rate_per_day;

                    $locked->charges()->create([
                        'type' => AdmissionChargeType::Room->value,
                        'description' => 'Kamar '.$locked->room->name.' ('.$locked->room->classLabel().')',
                        'quantity' => $days,
                        'unit_price' => $price,
                        'subtotal' => $price * $days,
                        'charged_at' => $discharged,
                        'created_by' => $userId,
                    ]);
                }
            }

            if ($locked->bed_id !== null) {
                Bed::query()->whereKey($locked->bed_id)->update(['status' => BedStatus::Available->value]);
            }

            $locked->update([
                'status' => AdmissionStatus::Discharged->value,
                'discharged_at' => $discharged,
                'discharge_notes' => $dischargeNotes,
            ]);

            $locked->movements()->create([
                'from_bed_id' => $locked->bed_id,
                'to_bed_id' => null,
                'reason' => 'Pulang rawat inap',
                'moved_at' => $discharged,
                'created_by' => $userId,
            ]);

            $locked->refresh();

            // Tanpa rincian biaya (mis. kamar gratis) tidak ada yang perlu ditagih.
            if ($locked->charges()->exists()) {
                $this->billing->createForAdmission($locked);
            }

            return $locked->refresh()->load(['patient', 'doctor', 'room', 'bed', 'invoice']);
        });
    }

    /**
     * @throws RuntimeException
     */
    public function cancel(Admission $admission, ?string $reason = null, ?int $userId = null): Admission
    {
        return DB::transaction(function () use ($admission, $reason, $userId) {
            /** @var Admission $locked */
            $locked = Admission::query()->lockForUpdate()->findOrFail($admission->id);

            if (! $locked->isActive()) {
                throw new RuntimeException('Hanya rawat inap aktif yang dapat dibatalkan.');
            }

            $bedId = $locked->bed_id;

            if ($bedId !== null) {
                Bed::query()->whereKey($bedId)->update(['status' => BedStatus::Available->value]);
            }

            $locked->update([
                'status' => AdmissionStatus::Cancelled->value,
                'discharge_notes' => trim('Dibatalkan: '.($reason ?: '-')),
            ]);

            $locked->movements()->create([
                'from_bed_id' => $bedId,
                'to_bed_id' => null,
                'reason' => 'Batal rawat inap',
                'moved_at' => now(),
                'created_by' => $userId,
            ]);

            return $locked->refresh()->load(['patient', 'doctor', 'room', 'bed']);
        });
    }
}
