@php
    $user = auth()->user();
    $current = trim(strip_tags(preg_replace('/\s+/', ' ', (string) $__env->yieldContent('page-breadcrumb'))));
@endphp

<header class="jhs-topbar">
    <button class="jhs-icon-btn d-lg-none" type="button" data-sidebar-toggle aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="flex-grow-1 min-w-0">
        @hasSection('page-breadcrumb')
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb jhs-breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route($user?->dashboardRoute() ?? 'landing') }}">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $current }}</li>
                </ol>
            </nav>
        @else
            <h1 class="jhs-topbar-title">@yield('topbar-title', $title ?? config('jhs.short_name'))</h1>
        @endif
    </div>

    <div class="d-none d-md-block jhs-global-search">
        <input type="search"
               class="form-control form-control-sm"
               style="min-width:250px"
               placeholder="Cari pasien, dokter, resep..."
               data-global-search="{{ route('search.global') }}"
               autocomplete="off"
               aria-label="Pencarian global">
        <div class="jhs-global-search-results" id="jhs-global-search-results"></div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="jhs-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                @if (($navBadges['waiting'] ?? 0) + ($navBadges['prescription'] ?? 0) + ($navBadges['low_stock'] ?? 0) > 0)
                    <span class="jhs-dot"></span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width:290px;border-radius:14px">
                <h6 class="dropdown-header px-2" style="font-size:.78rem">Aktivitas Perlu Perhatian</h6>
                @php
                    $alerts = array_filter([
                        ($navBadges['waiting'] ?? 0) > 0 ? ['Antrean menunggu', $navBadges['waiting'], 'bi-list-ol', 'warning'] : null,
                        ($navBadges['prescription'] ?? 0) > 0 ? ['Resep menunggu proses', $navBadges['prescription'], 'bi-file-earmark-medical', 'info'] : null,
                        ($navBadges['low_stock'] ?? 0) > 0 ? ['Obat stok rendah', $navBadges['low_stock'], 'bi-capsule', 'danger'] : null,
                    ]);
                @endphp
                @forelse ($alerts as [$label, $count, $icon, $color])
                    <a class="dropdown-item rounded-3 d-flex align-items-center gap-2 py-2"
                       href="{{ $user?->isPatient() ? route('patient.dashboard') : route($user?->dashboardRoute()) }}">
                        <i class="bi {{ $icon }} text-{{ $color }}"></i>
                        <span class="small">{{ $label }}</span>
                        <span class="badge text-bg-{{ $color }} ms-auto">{{ $count }}</span>
                    </a>
                @empty
                    <div class="px-2 py-3 text-center text-muted-2 fs-7">
                        <i class="bi bi-check2-circle d-block fs-4 text-success mb-1"></i>
                        Tidak ada hal yang perlu ditindak.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2 border-0 bg-transparent px-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="jhs-avatar jhs-avatar-sm">{{ $user?->initials() }}</span>
                <span class="d-none d-lg-block text-start lh-sm">
                    <span class="d-block fw-semibold" style="font-size:.82rem">{{ $user?->displayName() }}</span>
                    <span class="d-block text-muted-2" style="font-size:.7rem">{{ $user?->roleLabel() }}</span>
                </span>
                <i class="bi bi-chevron-down text-muted-2" style="font-size:.7rem"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:230px;border-radius:14px">
                <li class="px-3 py-2">
                    <div class="fw-semibold" style="font-size:.85rem">{{ $user?->displayName() }}</div>
                    <div class="text-muted-2" style="font-size:.75rem">{{ $user?->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                @if ($user?->isPatient())
                    <li><a class="dropdown-item small" href="{{ route('patient.profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profil Saya</a></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item small text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
