@extends('layout_dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    $current = $user->getDirectPermissions()->pluck('name')->toArray(); // direct perms only
@endphp

<div class="p-6 bg-white rounded-xl border">
    <h2 class="text-xl font-bold mb-4">Permissions for {{ $user->name }}</h2>

    <form method="POST" action="{{ route('users.permissions.update', $user) }}">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($permissions as $permission)
                @php
                    $id = 'perm-' . $user->id . '-' . Str::slug($permission->name);
                @endphp

                <label for="{{ $id }}" class="flex items-center gap-2 p-3 border rounded-lg">
                    <input
                        id="{{ $id }}"
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $permission->name }}"
                        {{ in_array($permission->name, $current) ? 'checked' : '' }}
                    >
                    <span>{{ $permission->name }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button class="px-4 py-2 bg-primary text-white rounded-lg" type="submit">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
