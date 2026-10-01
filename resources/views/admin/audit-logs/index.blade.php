@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
    <x-page-header title="Audit Log" description="Catatan setiap aktivitas penting pada sistem, tidak dapat dimodifikasi." icon="bi-shield-check" />

    <div class="row g-3 mb-3">
        <div class="col-4">
            <x-stat-card label="Hari Ini" :value="$stats['today']" icon="bi-calendar-day" color="primary" />
        </div>
        <div class="col-4">
            <x-stat-card label="7 Hari Terakhir" :value="$stats['week']" icon="bi-calendar-week" color="info" />
        </div>
        <div class="col-4">
            <x-stat-card label="Seluruh Aktivitas" :value="$stats['total']" icon="bi-database" color="secondary" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Aktivitas</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Deskripsi atau nama pengguna" data-search-submit>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="module">Modul</label>
                <select name="module" id="module" class="form-select form-select-sm">
                    <option value="">Semua Modul</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ ucfirst($module) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="action">Aksi</label>
                <select name="action" id="action" class="form-select form-select-sm">
                    <option value="">Semua Aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="user_id">Pengguna</label>
                <select name="user_id" id="user_id" class="form-select form-select-sm">
                    <option value="">Semua Pengguna</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="period">Periode</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" aria-label="Dari tanggal">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" aria-label="Sampai tanggal">
                </div>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$logs" :columns="[
        ['label' => 'Waktu', 'key' => 'time'],
        ['label' => 'Pengguna', 'key' => 'user'],
        ['label' => 'Modul', 'key' => 'module'],
        ['label' => 'Aksi', 'key' => 'action'],
        ['label' => 'Deskripsi', 'key' => 'description'],
        ['label' => 'IP', 'key' => 'ip'],
    ]" empty-title="Belum ada aktivitas" empty-icon="bi-shield-check">
        @forelse ($logs as $log)
            <tr onclick="window.location.href='{{ route('admin.audit-logs.show', $log) }}'">
                <td class="small text-muted-2 text-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$log->user->name ?? 'Sistem'" size="sm" />
                        <div class="min-w-0">
                            <div class="small fw-semibold text-truncate">{{ $log->user->name ?? 'Sistem' }}</div>
                            <div class="fs-7 text-muted-2">{{ optional($log->user)->roleLabel() ?? '-' }}</div>
                        </div>
                    </div>
                </td>
                <td><x-badge :text="ucfirst($log->module)" :color="$log->moduleBadge()" :icon="$log->moduleIcon()" /></td>
                <td class="small"><code class="fs-7">{{ $log->action }}</code></td>
                <td class="small">{{ $log->description }}</td>
                <td class="small text-muted-2">{{ $log->ip_address ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-shield-check" title="Belum ada aktivitas" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection