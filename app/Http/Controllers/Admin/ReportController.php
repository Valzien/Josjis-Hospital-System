<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.reports.index', [
            'filters' => $filters,
            'doctors' => Doctor::query()->orderBy('name')->get(),
            'patients' => Patient::query()->orderBy('name')->limit(500)->get(),
            'medicines' => Medicine::query()->orderBy('name')->get(),
            'available' => ReportService::availableReports(),
            'summary' => $this->reports->summary($filters),
            'patientTrend' => $this->reports->dailySeries($filters, 'patients'),
            'queueTrend' => $this->reports->dailySeries($filters, 'queues'),
        ]);
    }

    public function show(Request $request, string $report): View
    {
        abort_unless(ReportService::isAvailable($report), 404);

        $filters = $this->filters($request);

        return view('admin.reports.show', [
            'report' => $report,
            'title' => ReportService::title($report),
            'filters' => $filters,
            'doctors' => Doctor::query()->orderBy('name')->get(),
            'patients' => Patient::query()->orderBy('name')->limit(500)->get(),
            'medicines' => Medicine::query()->orderBy('name')->get(),
            'columns' => ReportService::columns($report),
            'rows' => $this->reports->rows($report, $filters),
            'totals' => $this->reports->totals($report, $filters),
            'chart' => $this->reports->chart($report, $filters),
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return [
            'from' => $request->date('from') ?? now()->subDays(29)->toDateString(),
            'to' => $request->date('to') ?? now()->toDateString(),
            'doctor_id' => $request->integer('doctor_id') ?: null,
            'patient_id' => $request->integer('patient_id') ?: null,
            'medicine_id' => $request->integer('medicine_id') ?: null,
            'status' => $request->string('status')->value() ?: null,
            'q' => $request->string('q')->trim()->value() ?: null,
        ];
    }
}
