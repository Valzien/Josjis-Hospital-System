<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Pulang {{ $admission->code }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .hospital { font-size: 14pt; font-weight: bold; }
        .meta { font-size: 8.5pt; color: #4b5563; }
        .doc-title { text-align: center; font-size: 12pt; font-weight: bold; margin: 16px 0 12px; letter-spacing: .5px; }
        h2 { font-size: 11pt; margin: 14px 0 6px; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8.5pt; width: 150px; }
        .vitals th { width: auto; text-align: center; font-size: 8pt; }
        .vitals td { text-align: center; }
        .notes { white-space: pre-line; }
        .sign { margin-top: 26px; width: 100%; }
        .sign td { border: none; padding: 0; }
        .sign .space { height: 52px; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="hospital">{{ config('jhs.name') }}</div>
        <div class="meta">
            {{ config('jhs.contact.address') }} &middot; {{ config('jhs.contact.phone') }}<br>
            Dicetak {{ now()->translatedFormat('l, d F Y H:i') }}
        </div>
    </div>

    <div class="doc-title">RINGKASAN PULANG RAWAT INAP</div>

    <h2>Data Pasien</h2>
    <table>
        <tr><th>Nama</th><td>{{ $admission->patient?->name ?? '-' }}</td></tr>
        <tr><th>No. Rekam Medis</th><td>{{ $admission->patient?->medical_record_number ?? '-' }}</td></tr>
        <tr><th>NIK</th><td>{{ $admission->patient?->nik ?? '-' }}</td></tr>
        <tr><th>Jenis Kelamin / Usia</th><td>{{ $admission->patient?->genderLabel() ?? '-' }} / {{ $admission->patient?->age() ? $admission->patient->age().' tahun' : '-' }}</td></tr>
        <tr><th>Golongan Darah</th><td>{{ $admission->patient?->blood_type?->label() ?? '-' }}</td></tr>
        @if ($admission->patient?->allergies)
            <tr><th>Alergi</th><td>{{ $admission->patient->allergies }}</td></tr>
        @endif
    </table>

    <h2>Data Rawat Inap</h2>
    <table>
        <tr><th>No. Rawat Inap</th><td>{{ $admission->code }}</td></tr>
        <tr><th>Dokter Penanggung Jawab</th><td>{{ $admission->doctor?->name ?? '-' }}</td></tr>
        <tr><th>Kamar / Tempat Tidur</th><td>
            {{ $admission->room?->name ?? '-' }}
            @if ($admission->bed) / {{ $admission->bed->code }} @endif
        </td></tr>
        <tr><th>Masuk</th><td>{{ $admission->admitted_at?->translatedFormat('d F Y H:i') ?? '-' }}</td></tr>
        <tr><th>Pulang</th><td>{{ $admission->discharged_at?->translatedFormat('d F Y H:i') ?? '-' }}</td></tr>
        <tr><th>Lama Inap</th><td>{{ $dayCount }} hari</td></tr>
        @if ($admission->complaint)
            <tr><th>Keluhan Utama</th><td class="notes">{{ $admission->complaint }}</td></tr>
        @endif
    </table>

    <h2>Diagnosis (ICD-10)</h2>
    <table>
        <thead>
            <tr>
                <th style="width:110px">Kode</th>
                <th>Diagnosis</th>
                <th style="width:150px">Jenis</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($admission->diagnoses as $diagnosis)
                <tr>
                    <td>{{ $diagnosis->code }}</td>
                    <td>
                        {{ $diagnosis->name }}
                        @if ($diagnosis->notes)
                            <div class="muted notes">{{ $diagnosis->notes }}</div>
                        @endif
                    </td>
                    <td>{{ $diagnosis->type?->label() ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">
                        {{ $admission->diagnosis ?: 'Belum ada diagnosis ICD-10 yang dicatat.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php($latest = $admission->latestVisit)
    @if ($latest)
        <h2>Tanda Vital Terakhir</h2>
        <table class="vitals">
            <thead>
                <tr>
                    <th>Suhu</th>
                    <th>Tekanan Darah</th>
                    <th>Nadi</th>
                    <th>Pernapasan</th>
                    <th>SpO₂</th>
                    <th>Berat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $latest->temperature ? $latest->temperature.' &#176;C' : '-' }}</td>
                    <td>{{ $latest->blood_pressure ? $latest->blood_pressure.' mmHg' : '-' }}</td>
                    <td>{{ $latest->pulse ? $latest->pulse.' x/menit' : '-' }}</td>
                    <td>{{ $latest->respiratory_rate ? $latest->respiratory_rate.' x/menit' : '-' }}</td>
                    <td>{{ $latest->oxygen_saturation ? $latest->oxygen_saturation.' %' : '-' }}</td>
                    <td>{{ $latest->weight ? $latest->weight.' kg' : '-' }}</td>
                </tr>
            </tbody>
        </table>
        <div class="muted" style="margin-top:4px">
            Dicatat {{ $latest->visited_at?->translatedFormat('d F Y H:i') ?? '-' }}
            @if ($latest->doctor) oleh {{ $latest->doctor->name }} @endif
        </div>
    @endif

    <h2>Ringkasan Pulang</h2>
    <table>
        <tr>
            <th>Kondisi Saat Pulang</th>
            <td class="notes">{{ $admission->discharge_condition ?: '-' }}</td>
        </tr>
        <tr>
            <th>Rencana Tindak Lanjut</th>
            <td class="notes">{{ $admission->discharge_plan ?: '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal Kontrol</th>
            <td>{{ $admission->follow_up_date?->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Catatan Pulang</th>
            <td class="notes">{{ $admission->discharge_notes ?: '-' }}</td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td style="width:60%"></td>
            <td>
                {{ config('jhs.contact.city', 'Kota Simulasi') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Dokter Penanggung Jawab
                <div class="space"></div>
                <strong>{{ $admission->doctor?->name ?? '................................' }}</strong>
            </td>
        </tr>
    </table>

    <footer>
        Ringkasan pulang ini dihasilkan otomatis oleh {{ config('jhs.name') }} dan sah tanpa tanda tangan basah.
    </footer>
</body>
</html>
