@php
    $flashes = [];
    if (session('success')) $flashes[] = ['success', 'Berhasil', session('success')];
    if (session('error'))   $flashes[] = ['error', 'Gagal', session('error')];
    if (session('warning')) $flashes[] = ['warning', 'Perhatian', session('warning')];
    if (session('status'))  $flashes[] = ['info', 'Informasi', session('status')];
@endphp

<div id="jhs-toast-container" class="jhs-toast-container" role="status" aria-live="polite">
    @foreach ($flashes as [$type, $title, $message])
        @php
            $icon = match ($type) {
                'success' => 'bi-check-circle-fill',
                'error' => 'bi-x-circle-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                default => 'bi-info-circle-fill',
            };
        @endphp
        <div class="jhs-toast jhs-toast-{{ $type }}">
            <i class="bi {{ $icon }} jhs-toast-icon"></i>
            <div class="flex-grow-1">
                <p class="jhs-toast-title">{{ $title }}</p>
                <p class="jhs-toast-text">{{ $message }}</p>
            </div>
            <button type="button" class="jhs-toast-close" aria-label="Tutup" onclick="this.closest('.jhs-toast').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endforeach
</div>

@if ($errors->any())
    <div class="position-fixed bottom-0 start-0 p-3" style="z-index:1090;max-width:420px">
        <div class="toast show border-0 shadow" role="alert">
            <div class="toast-header border-0" style="background:#fee2e2;color:#b91c1c">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                <strong class="me-auto">Periksa kembali input Anda</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
            <div class="toast-body">
                <ul class="mb-0 ps-3" style="font-size:.82rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
