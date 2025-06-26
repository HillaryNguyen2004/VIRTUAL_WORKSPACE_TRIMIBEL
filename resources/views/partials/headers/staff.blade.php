<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="{{ route('staff.dashboard') }}">Staff Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarStaff" aria-controls="navbarStaff" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarStaff">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">
                        <i class="bi bi-house me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tasks.staff.index') ? 'active' : '' }}" href="{{ route('tasks.staff.index') }}">
                        <i class="bi bi-list-task me-1"></i> My Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('team.overview') ? 'active' : '' }}" href="{{ route('team.overview') }}">
                        <i class="bi bi-people me-1"></i> My Team
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                {{-- Language Switcher --}}
                @php
                    $currentLocale = app()->getLocale();
                @endphp
                <li class="nav-item">
                    @if ($currentLocale === 'en')
                        <a class="nav-link text-white" href="#">
                            🇻🇳 <span class="ms-1 d-none d-md-inline">Vietnamese</span>
                        </a>
                    @elseif ($currentLocale === 'vi')
                        <a class="nav-link text-white" href="#">
                            🇺🇸 <span class="ms-1 d-none d-md-inline">English</span>
                        </a>
                    @endif
                </li>

                {{-- Authenticated User Info --}}
                <li class="nav-item">
                    <span class="nav-link text-white">
                        <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                    </span>
                </li>

                {{-- Logout --}}
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
