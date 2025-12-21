@extends('layout_dashboard')

@section('content')
    @role('admin')
    <div class="flex flex-col gap-6 w-full">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold">{{ __('projects.project_list') }}</h2>

            <a href="{{ route('projects.create') }}"
                class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:opacity-95">
                {{ __('projects.create_project') }}
            </a>
        </div>

        <form method="GET" class="flex flex-wrap gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="Search by project name"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            <select name="sort_dir" class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                <option value="">Default</option>
                <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>A → Z</option>
                <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>Z → A</option>
            </select>

            <input type="date" name="start_date" value="{{ request('start_date') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            <input type="date" name="due_date" value="{{ request('due_date') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            <div class="flex gap-2">
                {{-- Filter button --}}
                <button type="submit" title="{{ __('tasks.filter') }}"
                    class="border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                    </svg>
                </button>

                {{-- Reset button --}}
                <a href="{{ url()->current() }}" title="{{ __('tasks.reset') }}"
                    class="flex items-center justify-center border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                    </svg>
                </a>
            </div>
        </form>


        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('projects.title') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('projects.staff') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('projects.status') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('projects.start_date') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('projects.due_date') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('projects.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($projects as $project)
                        @php
                            $overdue = \Carbon\Carbon::parse($project->due_date)->isPast()
                                && $project->status !== 'completed';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $project->title }}</td>
                            <td class="px-4 py-3">{{ $project->staff->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 rounded text-xs
                                                {{ $project->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-600' }}">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </td>

                            <td class="py-3">
                                {{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}
                            </td>

                            <td class="py-3">
                                {{ \Carbon\Carbon::parse($project->due_date)->format('d/m/Y') }}
                                @if($overdue)
                                    <span class="text-red-500 ml-1">⚠</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right flex gap-2 justify-end">
                                <a href="{{ route('projects.edit', $project->id) }}" class="text-indigo-600 hover:underline">
                                    {{ __('projects.edit') }}
                                </a>

                                <form action="{{ route('projects.destroy', $project->id) }}" method="POST"
                                    onsubmit="return confirm('{{ __('projects.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-500 hover:underline">
                                        {{ __('projects.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400">
                                {{ __('projects.no_projects') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($projects->hasPages())
                <div class="my-4 flex justify-center w-full">
                    {{ $projects->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>

    </div>
    @endrole
@endsection