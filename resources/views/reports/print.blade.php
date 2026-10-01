<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1f2937; }
        header { border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 12px; }
        h1 { font-size: 14pt; margin: 0 0 2px; }
        .meta { font-size: 8.5pt; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; }
        th { background: #f3f4f6; font-size: 8.5pt; text-transform: uppercase; }
        td { font-size: 8.5pt; }
        tr:nth-child(even) td { background: #fafafa; }
        tfoot td { background: #eef2ff; font-weight: bold; }
        .totals { margin-bottom: 12px; }
        .totals td { border: none; padding: 2px 12px 2px 0; font-size: 8.5pt; }
        footer { margin-top: 16px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $title }}</h1>
        <div class="meta">
            {{ $hospital['name'] ?? config('jhs.name') }} &middot; {{ $hospital['contact']['address'] ?? '' }}<br>
            Periode: {{ \Illuminate\Support\Carbon::parse($filters['from'])->translatedFormat('d F Y') }}
            s/d {{ \Illuminate\Support\Carbon::parse($filters['to'])->translatedFormat('d F Y') }}
            @if ($filters['doctor_id']) &middot; Dokter: ID {{ $filters['doctor_id'] }} @endif
            <br>Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }}
        </div>
    </header>

    @if (! empty($totals))
        <table class="totals">
            @foreach ($totals as $label => $value)
                <tr>
                    <td>{{ $label }}</td>
                    <td><strong>{{ is_numeric($value) ? number_format((float) $value, 0, ',', '.') : $value }}</strong></td>
                </tr>
            @endforeach
        </table>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (array_slice($rows, 0, 500) as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        {{ count($rows) }} baris data{{ count($rows) > 500 ? ' (500 baris pertama ditampilkan pada PDF)' : '' }}
        &middot; Dokumen ini dihasilkan otomatis oleh sistem JOSJIS Hospital System.
    </footer>
</body>
</html>