<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi {{ $invoice->code }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .hospital { font-size: 14pt; font-weight: bold; }
        .meta { font-size: 8.5pt; color: #4b5563; text-align: right; }
        h2 { font-size: 11pt; margin: 14px 0 6px; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 9pt; }
        th { background: #f3f4f6; }
        tfoot td { background: #eef2ff; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .notes { margin-top: 10px; font-size: 9pt; }
        .sign { margin-top: 30px; text-align: right; font-size: 9pt; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8.5pt; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8pt; color: #6b7280; }
        .toolbar { margin-bottom: 12px; text-align: right; }
        .toolbar button { padding: 6px 14px; border: 1px solid #0d9488; background: #0d9488; color: #fff; border-radius: 4px; cursor: pointer; font-size: 9pt; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak Kuitansi</button>
    </div>

    <div class="header">
        <div>
            <div class="hospital">{{ config('jhs.name') }}</div>
            <div class="meta">
                {{ config('jhs.contact.address') }}<br>
                {{ config('jhs.contact.phone') }}
            </div>
        </div>
        <div class="meta">
            <strong style="font-size: 11pt; color: #0f766e;">KUITANSI {{ $invoice->code }}</strong><br>
            {{ $invoice->created_at?->translatedFormat('d F Y H:i') }}<br>
            <span class="status status-{{ strtolower($invoice->status?->value ?? 'unpaid') }}">
                {{ $invoice->status?->label() ?? '-' }}
            </span>
        </div>
    </div>

    <h2>Data Pasien</h2>
    <table>
        <tr><th style="width: 140px;">Nama</th><td>{{ $invoice->patient?->name ?? '-' }}</td></tr>
        <tr><th>No. Rekam Medis</th><td>{{ $invoice->patient?->medical_record_number ?? '-' }}</td></tr>
        <tr><th>Dokter</th><td>{{ $invoice->queue?->doctor?->name ?? '-' }}</td></tr>
        <tr><th>No. Antrean</th><td>{{ $invoice->queue?->queue_number ?? '-' }}</td></tr>
    </table>

    <h2>Rincian Biaya</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 26px;">#</th>
                <th>Komponen</th>
                <th class="center" style="width: 60px;">Qty</th>
                <th class="right" style="width: 110px;">Harga</th>
                <th class="right" style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Subtotal</td>
                <td class="right">Rp {{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ((float) $invoice->discount > 0)
                <tr>
                    <td colspan="4" class="right">Potongan</td>
                    <td class="right">- Rp {{ number_format((float) $invoice->discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td colspan="4" class="right">Total Tagihan</td>
                <td class="right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right">Sudah Dibayar</td>
                <td class="right">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right">Sisa Tagihan</td>
                <td class="right">Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <h2>Riwayat Pembayaran</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Metode</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->code }}</td>
                        <td>{{ $payment->methodLabel() }}</td>
                        <td>{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</td>
                        <td>{{ $payment->receivedBy?->name ?? '-' }}</td>
                        <td class="right">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($invoice->notes)
        <div class="notes"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
    @endif

    <div class="sign">
        {{ config('jhs.name') }}<br>
        <strong>{{ $invoice->issuedBy?->name ?? 'Kasir' }}</strong>
    </div>

    <footer>
        Kuitansi ini dicetak oleh sistem {{ config('jhs.name') }} pada {{ now()->translatedFormat('d F Y H:i') }}.
        Terima kasih atas kepercayaan Anda.
    </footer>
</body>
</html>
