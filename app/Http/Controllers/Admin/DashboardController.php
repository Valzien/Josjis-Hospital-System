<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\Receptionist;
use App\Models\Setting;
use App\Models\User;
use App\Services\MedicineService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MedicineService $medicines): View
    {
        $today = now()->toDateString();

        $counts = [
            'patients' => Patient::query()->count(),
            'doctors' => Doctor::query()->active()->count(),
            'pharmacists' => Pharmacist::query()->count(),
            'receptionists' => Receptionist::query()->count(),
            'users' => User::query()->count(),
            'queues_today' => Queue::query()->onDate($today)->count(),
            'waiting_today' => Queue::query()->onDate($today)->where('status', QueueStatus::Waiting->value)->count(),
            'exams_today' => MedicalRecord::query()->whereDate('examined_at', $today)->count(),
            'prescriptions_today' => Prescription::query()->whereDate('created_at', $today)->count(),
            'low_stock' => $medicines->lowStockCount(),
        ];

        $monthlyPatients = Patient::query()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw($this->monthExpression().' as period, count(*) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $queueStatus = Queue::query()
            ->onDate($today)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $serviceDistribution = MedicalRecord::query()
            ->whereDate('examined_at', '>=', now()->subDays(29)->toDateString())
            ->join('doctors', 'doctors.id', '=', 'medical_records.doctor_id')
            ->select('doctors.specialization', DB::raw('count(*) as total'))
            ->groupBy('doctors.specialization')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return view('admin.dashboard', [
            'counts' => $counts,
            'monthlyPatients' => $this->fillMonths($monthlyPatients),
            'queueStatus' => $queueStatus,
            'serviceDistribution' => $serviceDistribution,
            'todayQueues' => Queue::query()->with(['patient', 'doctor'])->onDate($today)->orderBy('queue_number')->limit(8)->get(),
            'recentActivities' => AuditLog::query()->with('user')->latest()->limit(10)->get(),
            'topDoctors' => Doctor::query()
                ->withCount('medicalRecords')
                ->active()
                ->orderByDesc('medical_records_count')
                ->limit(5)
                ->get(),
            'inventoryValue' => $medicines->inventoryValue(),
            'hospitalName' => Setting::get('hospital_name', config('jhs.name')),
        ]);
    }

    /**
     * Ekspresi SQL untuk mengelompokkan tanggal per bulan, kompatibel MySQL & SQLite.
     */
    private function monthExpression(string $column = 'created_at'): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Melengkapi data bulanan yang kosong agar chart tidak bolong.
     *
     * @param  Collection<string, int>  $rows
     * @return array<string, array{period: string, total: int}>
     */
    private function fillMonths($rows): array
    {
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $series[] = [
                'period' => $date->translatedFormat('M Y'),
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }
}
