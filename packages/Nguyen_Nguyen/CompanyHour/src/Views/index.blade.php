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
        <div class="flex flex-col md:flex-row justify-between items-center gap-2">
            <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('company_hour.title') }}</h2>

            @if (!$hour)
                <a href="{{ route('companyhour.create') }}"
                    class="flex items-center justify-center gap-1 w-fit h-fit bg-[#5D3FD3] text-white px-3 py-1 rounded-xl hover:opacity-95">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h4 fill-white">
                        <path
                            d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                    </svg>
                    {{ __('company_hour.add_btn') }}
                </a>
            @endif
        </div>

        <div
            class="overflow-x-auto rounded-2xl border border-gray-300 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:200ms]">
            <table class="w-full">
                <thead class="bg-gray-100 text-gray-500 w uppercase tracking-wide text-sm">
                    <tr>
                        <th class="py-3 pl-4 pr-3 text-left font-medium">ID</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('company_hour.start_time') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('company_hour.end_time') }}</th>
                        <th class="py-3 pr-4 pl-3 text-right font-medium"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-[#FDFDFF] text-sm">
                    @if($hour)
                        <tr>
                            {{-- Id --}}
                            <td class="py-3 pl-4 pr-3">
                                <div class="max-w-xs truncate">{{ $hour->id }}</div>
                            </td>

                            {{-- Start time --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate">{{ \Carbon\Carbon::parse($hour->start_at)->format('H:i') }}</div>
                            </td>

                            {{-- End time --}}
                            <td class="py-3 px-3">
                                <div class="max-wxs truncate">{{ \Carbon\Carbon::parse($hour->end_at)->format('H:i') }}</div>
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 pr-4 pl-3 text-right">
                                <div class="flex items-center justify-end gap-1 h-fit">
                                    {{-- Edit --}}
                                    <a href="{{ route('companyhour.edit', $hour->id) }}"
                                        class="p-1.5 rounded-full hover:bg-gray-100 transition" title="{{ __('tasks.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                            class="w-5 h-5 fill-green-500">
                                            <path
                                                d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z" />
                                        </svg>
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('companyhour.destroy', $hour->id) }}" method="POST"
                                        onsubmit="return confirm('Are you sure?');" class="">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-full hover:bg-gray-100 transition"
                                            title="{{ __('tasks.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                class="w-5 h-5 fill-red-600">
                                                <path
                                                    d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                {{ __('company_hour.no_company_hour') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-action-layout>

    <!-- <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="fw-bold text-primary">Company Working Hours</h2>

                    @if (!$hour)
                        <a href="{{ route('companyhour.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Company Hours
                        </a>
                    @endif
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        @if ($hour)
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $hour->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($hour->start_at)->format('H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($hour->end_at)->format('H:i') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('companyhour.edit') }}" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <form action="{{ route('companyhour.destroy', $hour->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted text-center">No company hours set yet.</p>
                        @endif
                    </div>
                </div>
            </div> -->
@endsection