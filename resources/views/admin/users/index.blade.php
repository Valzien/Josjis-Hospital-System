@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <x-page-header title="Pengguna" description="Kelola akun, role, dan status akses seluruh petugas serta pasien." icon="bi-people">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Pengguna
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="q">Cari Pengguna</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama atau email" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="role">Role</label>
                <select name="role" id="role" class="form-select form-select-sm">
                    <option value="">Semua Role</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$users" :columns="[
        ['label' => 'Pengguna', 'key' => 'user'],
        ['label' => 'Role', 'key' => 'role'],
        ['label' => 'Kontak', 'key' => 'contact'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Login Terakhir', 'key' => 'last_login'],
        ['label' => 'Aksi', 'key' => 'actions', 'cellClass' => 'text-end'],
    ]" empty-title="Belum ada pengguna" empty-text="Coba ubah kata kunci atau filter pencarian." empty-icon="bi-people">
        @forelse ($users as $user)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <x-avatar :name="$user->name" size="sm" />
                        <div class="min-w-0">
                            <div class="fw-semibold small text-truncate">{{ $user->name }}</div>
                            <div class="fs-7 text-muted-2 text-truncate">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge text-bg-light">{{ $user->roleLabel() }}</span></td>
                <td class="small text-muted-2">{{ $user->phone ?? '-' }}</td>
                <td>
                    <x-badge :text="$user->status->label()"
                            :color="$user->isActive() ? 'success' : 'secondary'"
                            :icon="$user->isActive() ? 'bi-check-circle-fill' : 'bi-slash-circle'" />
                </td>
                <td class="small text-muted-2">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-light border" title="Ubah">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-light border" title="Atur ulang kata sandi"
                                data-bs-toggle="modal" data-bs-target="#resetPassword{{ $user->id }}">
                            <i class="bi bi-key"></i>
                        </button>
                        @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline"
                                  data-confirm-submit
                                  data-confirm-title="Ubah Status"
                                  data-confirm-message="Ubah status akses akun {{ $user->name }}?"
                                  data-confirm-variant="warning">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-light border" title="{{ $user->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="bi bi-{{ $user->isActive() ? 'toggle-on' : 'toggle-off' }}"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                  data-confirm-submit
                                  data-confirm-title="Hapus Pengguna"
                                  data-confirm-message="Hapus akun {{ $user->name }} secara permanen?"
                                  data-confirm-variant="danger">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-light border" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-empty-state icon="bi-people" title="Belum ada pengguna"
                                   text="Coba ubah kata kunci atau filter pencarian." />
                </td>
            </tr>
        @endforelse
    </x-data-table>

    @foreach ($users as $user)
        <div class="modal fade" id="resetPassword{{ $user->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.users.password', $user) }}">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Atur Ulang Kata Sandi</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted-2">Kata sandi baru untuk <strong>{{ $user->name }}</strong>.</p>
                            <div class="mb-3">
                                <label class="form-label small mb-1" for="newPassword{{ $user->id }}">Kata Sandi Baru</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="newPassword{{ $user->id }}"
                                           class="form-control" required minlength="8" autocomplete="new-password">
                                    <button class="btn btn-light border" type="button" data-toggle-password="#newPassword{{ $user->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label small mb-1" for="confirmPassword{{ $user->id }}">Konfirmasi Kata Sandi</label>
                                <input type="password" name="password_confirmation" id="confirmPassword{{ $user->id }}"
                                       class="form-control" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection