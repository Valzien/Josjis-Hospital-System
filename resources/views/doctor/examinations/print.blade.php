<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekam Medis {{ $record->record_number }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .hospital { font-size: 14pt; font-weight: bold; }
        .meta { font-size: 8.5pt; color: #4b5563; }
        h2 { font-size: 11pt; margin: 14px 0 6px; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-size: 8.5pt; width: 130px; }
        .vitals td { text-align: center; }
        .vitals th { width: auto; text-align: center; }
        .notes { white-space: pre-line; }
        footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="hospital">{{ config('jhs.name') }}</div>
        <div class="meta">
            {{ config('jhs.contact.address') }} &middot; {{ config('jhs.contact.phone') }}<br>
            {{ now()->translatedFormat('l, d F Y H:i') }}
        </div>
    </div>

    <h2>Data Pasien</h2>
    <table>
        <tr><th>Nama</th><td>{{ $record->patient->name }}</td></tr>
        <tr><th>No. Rekam Medis</th><td>{{ $record->patient->medical_record_number }}</td></tr>
        <tr><th>NIK</th><td>{{ $record->patient->nik ?? '-' }}</td></tr>
        <tr><th>Jenis Kelamin / Usia</th><td>{{ $record->patient->genderLabel() }} / {{ $record->patient->age() ? $record->patient->age().' tahun' : '-' }}</td></tr>
        <tr><th>Golongan Darah</th><td>{{ $record->patient->blood_type?->label() ?? '-' }}</td></tr>
        @if ($record->patient->allergies)
            <tr><th>Alergi</th><td>{{ $record->patient->allergies }}</td></tr>
        @endif
    </table>

    <h2>Tanda Vital</h2>
    <table class="vitals">
        <thead>
            <tr>
                <th>Suhu</th>
                <th>Tekanan Darah</th>
                <th>Berat Badan</th>
                <th>Tinggi Badan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $record->temperature ? $record->temperature.' &#176;C' : '-' }}</td>
                <td>{{ $record->blood_pressure ? $record->blood_pressure.' mmHg' : '-' }}</td>
                <td>{{ $record->weight ? $record->weight.' kg' : '-' }}</td>
                <td>{{ $record->height ? $record->height.' cm' : '-' }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Hasil Pemeriksaan</h2>
    <table>
        <tr><th>Dokter</th><td>{{ $record->doctor?->name }}</td></tr>
        <tr><th>Waktu</th><td>{{ $record->examined_at?->translatedFormat('d F Y H:i') }}</td></tr>
        <tr><th>Keluhan</th><td>{{ $record->complaint ?? '-' }}</td></tr>
        <tr><th>Hasil</th><td class="notes">{{ $record->examination_result ?? '-' }}</td></tr>
        <tr><th>Diagnosis</th><td>{{ $record->diagnosis ?? '-' }}</td></tr>
        <tr><th>Tindakan</th><td>{{ $record->treatment ?? '-' }}</td></tr>
        <tr><th>Catatan</th><td class="notes">{{ $record->notes ?? '-' }}</td></tr>
    </table>

    @if ($record->prescription)
        <h2>Resep ({{ $record->prescription->code }})</h2>
        <table>
            <thead>
                <tr>
                    <th>Obat</th>
                    <th>Jumlah</th>
                    <th>Dosis</th>
                    <th>Aturan Pakai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($record->prescription->details as $detail)
                    <tr>
                        <td>{{ $detail->medicine->name }}</td>
                        <td>{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                        <td>{{ $detail->dosage ?? '-' }}</td>
                        <td>{{ $detail->instructions ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <footer>
        Rekam medis ini dihasilkan otomatis oleh {{ config('jhs.name') }} dan sah tanpa tanda tangan basah.
    </footer>
</body>
</html>