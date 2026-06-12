@extends('layout_dashboard')

@section('content')
<div class="w-full max-w-[1200px] mx-auto px-4 md:px-8 lg:px-16 xl:px-24 py-8 text-main">

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold tracking-tight">Subadmin Manager</h2>
            <p class="text-sm text-muted-500 mt-2">
                Pick a user to become a subadmin and configure their direct permissions.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.subadmins.index') }}" class="flex gap-2">
            <input
                name="q"
                value="{{ $q }}"
                placeholder="Search name/email..."
                class="px-3 py-2 rounded-xl border border-muted-200 bg-white"
            />
            <button class="px-3 py-2 rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                Search
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-secondary/10 text-secondary border border-secondary/20 text-sm font-medium px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 border border-red-200 text-sm font-medium px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-muted-200 rounded-2xl overflow-hidden">
        <div class="divide-y divide-muted-100">
            @foreach($users as $u)
                <div class="p-4 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{{ $u->name }}</div>
                        <div class="text-sm text-muted-500 truncate">{{ $u->email }}</div>
                        <div class="text-xs text-muted-500 mt-1">
                            Roles: {{ $u->getRoleNames()->implode(', ') ?: 'none' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($u->hasRole('subadmin'))
                            
                            <a href="{{ route('admin.subadmins.permissions.edit', $u) }}"
                               class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                                Edit permissions
                            </a>
                        @else
                            <form method="POST" action="{{ route('admin.subadmins.make', $u) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-2 text-sm rounded-xl bg-primary text-white hover:bg-primary-hover transition">
                                    Create subadmin
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
