<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportExportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function export(Request $request, string $report, string $format)
    {
        abort_unless(ReportService::isAvailable($report), 404);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = [
            'from' => $request->date('from') ?? now()->subDays(29)->toDateString(),
            'to' => $request->date('to') ?? now()->toDateString(),
            'doctor_id' => $request->integer('doctor_id') ?: null,
            'patient_id' => $request->integer('patient_id') ?: null,
            'medicine_id' => $request->integer('medicine_id') ?: null,
            'status' => $request->string('status')->value() ?: null,
            'q' => $request->string('q')->trim()->value() ?: null,
        ];

        $columns = ReportService::columns($report);
        $rows = $this->reports->rows($report, $filters);
        $title = ReportService::title($report);
        $filename = str($report)->slug()->append('-'.now()->format('Ymd-His'))->value();

        return match ($format) {
            'csv' => $this->csv($title, $columns, $rows, $filename),
            'xlsx' => $this->csv($title, $columns, $rows, $filename.'-excel'),
            'pdf' => $this->pdf($title, $columns, $rows, $filters),
        };
    }

    /**
     * Ekspor CSV yang kompatibel dengan Excel (pemisah titik koma + BOM UTF-8).
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csv(string $title, array $columns, array $rows, string $filename): Response
    {
        $buffer = fopen('php://temp', 'r+');

        fwrite($buffer, "\xEF\xBB\xBF");
        fputcsv($buffer, [$title, 'Periode: '.request('from').' s/d '.request('to'), 'Dicetak: '.now()->format('d/m/Y H:i')], ';');
        fputcsv($buffer, [], ';');
        fputcsv($buffer, $columns, ';');

        foreach ($rows as $row) {
            fputcsv($buffer, array_map(fn ($v) => (string) $v, $row), ';');
        }

        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function pdf(string $title, array $columns, array $rows, array $filters)
    {
        $pdf = Pdf::loadView('reports.print', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $filters,
            'totals' => $this->reports->totals(
                (string) request()->route('report'),
                $filters
            ),
            'generatedAt' => now(),
            'hospital' => config('jhs'),
        ])->setPaper('a4', count($columns) > 6 ? 'landscape' : 'portrait');

        return $pdf->download(str($title)->slug()->append('-'.now()->format('Ymd-His')).value().'.pdf');
    }
}
