@php
    $user = auth()->user();
    $role = $user?->role;
    $items = config('jhs.navigation.'.($role?->value ?? 'patient'), []);
@endphp

<aside class="jhs-sidebar" id="jhs-sidebar">
    <a class="jhs-sidebar-brand text-decoration-none" href="{{ route($user?->dashboardRoute() ?? 'landing') }}">
        <div class="jhs-sidebar-brand-icon">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ $jhsConfig['short_name'] }}">
        </div>
        <div class="jhs-brand-text d-none d-sm-block">
            <strong>{{ $jhsConfig['short_name'] }}</strong>
            <span>Hospital System</span>
        </div>
    </a>

    <nav class="jhs-sidebar-nav">
        @foreach ($items as $item)
            @if (! empty($item['section']))
                <div class="jhs-nav-section">{{ $item['section'] }}</div>
            @endif

            @php
                $active = request()->routeIs($item['route'])
                    || (request()->routeIs($item['route'].'.*'));
                $badgeValue = $item['badge'] ?? null;
                $badgeCount = $badgeValue ? ($navBadges[$badgeValue] ?? 0) : 0;
            @endphp

            <a href="{{ route($item['route']) }}"
               class="jhs-nav-link {{ $active ? 'active' : '' }}"
               @if ($active) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
                @if ($badgeCount > 0)
                    <span class="jhs-nav-badge">{{ $badgeCount > 99 ? '99+' : $badgeCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($user)
        <div class="jhs-sidebar-footer">
            <div class="jhs-sidebar-user">
                <span class="jhs-avatar" style="background:#2563eb;color:#fff;border:none;">{{ $user->initials() }}</span>
                <div class="flex-grow-1 min-w-0">
                    <div class="jhs-sidebar-user-name text-truncate">{{ $user->displayName() }}</div>
                    <div class="jhs-sidebar-user-role text-truncate">{{ $user->roleLabel() }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="jhs-action-btn" type="submit" title="Keluar" style="background:transparent;border:none;color:#94a3b8">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    @endif
</aside>

<div class="jhs-sidebar-backdrop" id="jhs-sidebar-backdrop"></div>
