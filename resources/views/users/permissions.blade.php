@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp
    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">
        <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('user_permission.title') }}</h2>

        @if(session('success'))
            <div
                class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194] text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">
                {{ session('success') }}</div>
        @endif

        @foreach ($roles as $role)
            @if ($role->name !== 'admin')
                <form action="{{ route('admin.permissions.update') }}"
                      method="POST"
                      class="flex flex-col gap-3 w-full h-fit py-3 px-4 border rounded-2xl animate-fade-in-up [animation-delay:150ms]">
                    @csrf

                    {{-- hidden role name --}}
                    <input type="hidden" name="role_name" value="{{ $role->name }}">

                    {{-- title + user count --}}
                    <h5 class="text-lg font-medium">
                        {{ ucfirst($role->name) }}
                        <span>
                            ({{ \App\Models\User::role($role->name)->whereHas('roles', function ($q) {
                                $q->where('name', '!=', 'admin');
                            })->count() }} users)
                        </span>
                    </h5>

                    {{-- checkbox permission --}}
                    <div class="flex flex-wrap justify-between gap-3">
                        @foreach ($permissions as $permission)
                            <div class="flex gap-2">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission->name }}"
                                       id="perm-{{ $role->id }}-{{ $permission->name }}"
                                       {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                <label for="perm-{{ $role->id }}-{{ $permission->name }}">
                                    {{ __('user_permission.' . str_replace('-', '_', $permission->name)) }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    {{-- button --}}
                    <div class="flex items-center justify-center w-full">
                        <button type="submit"
                                class="flex gap-2 items-center justify-center px-4 py-2 mt-4 w-full sm:w-fit bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                 class="w-5 h-5 fill-white">
                                <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z"/>
                            </svg>
                            {{ __('user_permission.update_button') }}
                        </button>
                    </div>
                </form>
            @endif
        @endforeach
    </x-action-layout>
@endsection
