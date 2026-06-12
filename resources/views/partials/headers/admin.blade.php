<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="{{ route('admin.dashboard') }}">Admin Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people me-1"></i> Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}" href="{{ route('admin.users.create') }}">
                        <i class="bi bi-person-plus me-1"></i> Add User
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.permissions') ? 'active' : '' }}" href="{{ route('admin.permissions') }}">
                        <i class="bi bi-shield-lock me-1"></i> Permissions
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                {{-- 🌐 Language Switcher --}}
                @php $currentLocale = app()->getLocale(); @endphp
                <li class="nav-item">
                    @if ($currentLocale === 'en')
                        <a class="nav-link text-white" href="{{ route('lang.switch', 'vi') }}">
                            🇻🇳 <span class="d-none d-md-inline ms-1">Vietnamese</span>
                        </a>
                    @elseif ($currentLocale === 'vi')
                        <a class="nav-link text-white" href="{{ route('lang.switch', 'en') }}">
                            🇺🇸 <span class="d-none d-md-inline ms-1">English</span>
                        </a>
                    @endif
                </li>

                <li class="nav-item">
                    <span class="nav-link text-white">
                        <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                    </span>
                </li>
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-link nav-link text-white" type="submit">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
