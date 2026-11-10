<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Karcis Antrean {{ $queue->queue_number }} - {{ config('jhs.short_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 24px 12px;
        }
        .jhs-ticket {
            width: 100%;
            max-width: 340px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .jhs-ticket-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .jhs-ticket-title {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin: 0;
            color: #1e293b;
        }
        .jhs-ticket-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }
        .jhs-ticket-body {
            text-align: center;
        }
        .jhs-ticket-number-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .jhs-ticket-number {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -1px;
            color: #0284c7;
            margin-bottom: 10px;
        }
        .jhs-ticket-meta {
            text-align: left;
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px dashed #cbd5e1;
            padding: 12px 0;
            margin: 14px 0;
            font-size: 0.82rem;
        }
        .jhs-ticket-meta dl {
            margin: 0;
        }
        .jhs-ticket-meta dt {
            color: #64748b;
            font-weight: 500;
        }
        .jhs-ticket-meta dd {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .jhs-ticket-footer {
            text-align: center;
            font-size: 0.72rem;
            color: #64748b;
            line-height: 1.4;
        }
        .jhs-actions {
            max-width: 340px;
            margin: 16px auto 0;
            display: flex;
            gap: 8px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .jhs-ticket {
                box-shadow: none;
                border: none;
                padding: 4px;
                max-width: 100%;
                width: 100%;
            }
            .jhs-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="jhs-ticket">
        <div class="jhs-ticket-header">
            <h1 class="jhs-ticket-title">{{ config('jhs.name') }}</h1>
            <div class="jhs-ticket-subtitle">{{ config('jhs.contact.address') }} &middot; {{ config('jhs.contact.phone') }}</div>
            <div class="jhs-ticket-subtitle mt-1">
                {{ $queue->queue_date?->translatedFormat('d M Y') }} &middot; {{ now()->translatedFormat('H:i') }} WIB
            </div>
        </div>

        <div class="jhs-ticket-body">
            <div class="jhs-ticket-number-label">Nomor Antrean Anda</div>
            <div class="jhs-ticket-number">{{ $queue->queue_number }}</div>

            <div class="jhs-ticket-meta">
                <dl class="row g-1">
                    <dt class="col-4">Pasien</dt>
                    <dd class="col-8 text-truncate">{{ $queue->patient?->name ?? '-' }}</dd>

                    <dt class="col-4">No. RM</dt>
                    <dd class="col-8">{{ $queue->patient?->medical_record_number ?? '-' }}</dd>

                    <dt class="col-4">Dokter</dt>
                    <dd class="col-8 text-truncate">{{ $queue->doctor?->name ?? '-' }}</dd>

                    <dt class="col-4">Spesialis</dt>
                    <dd class="col-8">{{ $queue->doctor?->specialization ?? '-' }}</dd>

                    <dt class="col-4">Ruang</dt>
                    <dd class="col-8">{{ $queue->doctorSchedule?->room ?? 'Poli' }} ({{ $queue->doctorSchedule?->timeRange() }})</dd>
                </dl>
            </div>

            <div class="jhs-ticket-footer">
                <div>Harap menunggu di ruang tunggu.</div>
                <div>Perhatikan nomor pemanggilan di layar antrean.</div>
                <div class="fw-bold mt-1">&mdash; Semoga Lekas Sembuh &mdash;</div>
            </div>
        </div>
    </div>

    <div class="jhs-actions">
        <button type="button" class="btn btn-primary flex-grow-1 btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Cetak Karcis
        </button>
        @if (isset($isPatient) && $isPatient)
            <a href="{{ route('patient.queue.show', $queue) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        @else
            <a href="{{ route('reception.queues.show', $queue) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        @endif
    </div>

    <script>
        // Otomatis buka dialog cetak jika ada parameter ?autoprint=1
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.addEventListener('load', function() {
                window.print();
            });
        }
    </script>
</body>
</html>

