@extends('layout_dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    $current = $user->getDirectPermissions()->pluck('name')->toArray();
@endphp

<div class="w-full max-w-[1200px] mx-auto px-4 md:px-8 lg:px-16 xl:px-24 py-8 text-main">

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold tracking-tight">
                Subadmin Permissions: {{ $user->name }}
            </h2>
            <p class="text-sm text-muted-500 mt-2">
                Direct permissions only (each subadmin can be different).
            </p>
        </div>

        <a href="{{ route('admin.subadmins.index') }}"
           class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
            Back
        </a>
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

    <form method="POST" action="{{ route('admin.subadmins.permissions.update', $user) }}"
          class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($permissions as $permission)
                @php $id = 'perm-' . $user->id . '-' . Str::slug($permission->name); @endphp

                <label for="{{ $id }}"
                       class="flex items-center gap-3 p-3 rounded-xl border border-muted-100 hover:bg-muted-50 cursor-pointer transition-colors">
                    <input
                        id="{{ $id }}"
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->name }}"
                        class="h-5 w-5 rounded-md border-muted-300 text-primary focus:ring-primary/20"
                        {{ in_array($permission->name, $current) ? 'checked' : '' }}
                    >
                    <span class="text-sm font-medium text-muted-700">{{ $permission->name }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    type="submit">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
