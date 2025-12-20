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