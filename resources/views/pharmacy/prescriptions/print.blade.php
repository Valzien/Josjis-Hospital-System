<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Resep {{ $prescription->code }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .hospital { font-size: 14pt; font-weight: bold; }
        .meta { font-size: 8.5pt; color: #4b5563; }
        .meta-right { text-align: right; }
        h2 { font-size: 11pt; margin: 14px 0 6px; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 9pt; }
        th { background: #f3f4f6; }
        tfoot td { background: #eef2ff; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .notes { margin-top: 10px; font-size: 9pt; }
        .sign { margin-top: 30px; font-size: 9pt; }
        footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="hospital">{{ config('jhs.name') }}</div>
            <div class="meta">
                {{ config('jhs.contact.address') }}<br>
                {{ config('jhs.contact.phone') }}
            </div>
        </div>
        <div class="meta meta-right">
            <strong style="font-size: 11pt; color: #0f766e;">{{ $prescription->code }}</strong><br>
            {{ $prescription->created_at?->translatedFormat('d F Y H:i') }}
        </div>
    </div>

    <h2>Data Pasien</h2>
    <table>
        <tr><th>Nama</th><td>{{ $prescription->patient->name }}</td></tr>
        <tr><th>No. Rekam Medis</th><td>{{ $prescription->patient->medical_record_number }}</td></tr>
        <tr><th>Dokter</th><td>{{ $prescription->doctor?->name }}</td></tr>
        <tr><th>Diagnosis</th><td>{{ $prescription->medicalRecord?->diagnosis ?? '-' }}</td></tr>
    </table>

    <h2>Resep</h2>
    <table>
        <thead>
            <tr>
                <th style="width:26px">#</th>
                <th>Obat</th>
                <th class="center" style="width:80px">Jumlah</th>
                <th style="width:110px">Dosis</th>
                <th>Aturan Pakai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($prescription->details as $index => $detail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $detail->medicine->name }}</td>
                    <td class="center">{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                    <td>{{ $detail->dosage ?? '-' }}</td>
                    <td>{{ $detail->instructions ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Total</td>
                <td class="right">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($prescription->notes)
        <div class="notes"><strong>Catatan dokter:</strong> {{ $prescription->notes }}</div>
    @endif

    <div class="sign">
        <strong>{{ $prescription->doctor?->name }}</strong><br>
        {{ $prescription->doctor?->specialization ?? 'Dokter' }}
    </div>

    <footer>
        Status: {{ $prescription->status?->label() ?? '-' }}
        @if ($prescription->processedBy) &middot; Apoteker: {{ $prescription->processedBy->name }} @endif
        &middot; Dicetak {{ now()->translatedFormat('d F Y H:i') }}
    </footer>
</body>
</html>