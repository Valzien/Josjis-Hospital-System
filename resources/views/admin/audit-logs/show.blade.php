@extends('layouts.app')

@section('title', 'Detail Audit Log')

@section('content')
    <x-page-header title="Detail Audit Log" :description="'Aksi '.$log->action.' pada modul '.$log->module" icon="bi-shield-check">
        <x-slot:actions>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Informasi Aktivitas" icon="bi-info-circle">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted-2 fw-normal">Waktu</dt>
                    <dd class="col-sm-9">{{ $log->created_at?->translatedFormat('l, d F Y H:i:s') }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Pengguna</dt>
                    <dd class="col-sm-9">
                        {{ $log->user->name ?? 'Sistem' }}
                        @if ($log->user)
                            <span class="badge text-bg-light ms-1">{{ $log->user->roleLabel() }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Modul</dt>
                    <dd class="col-sm-9">
                        <x-badge :text="ucfirst($log->module)" :color="$log->moduleBadge()" :icon="$log->moduleIcon()" />
                    </dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Aksi</dt>
                    <dd class="col-sm-9"><code>{{ $log->action }}</code></dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Deskripsi</dt>
                    <dd class="col-sm-9">{{ $log->description }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Alamat IP</dt>
                    <dd class="col-sm-9">{{ $log->ip_address ?? '-' }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">User Agent</dt>
                    <dd class="col-sm-9 small text-break">{{ $log->user_agent ?? '-' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Properti Tambahan" icon="bi-braces">
                @if (! empty($log->properties))
                    <pre class="small bg-body-tertiary p-3 rounded-3 mb-0" style="max-height:420px;overflow:auto"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                @else
                    <x-empty-state icon="bi-braces" title="Tidak ada properties" text="Aksi ini tidak menyimpan metadata tambahan." />
                @endif
            </x-card>
        </div>
    </div>
@endsection