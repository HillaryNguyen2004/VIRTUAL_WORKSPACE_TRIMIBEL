@extends('layout_dashboard')

@section('content')
@role('admin')
<div class="max-w-3xl bg-white p-6 rounded-xl shadow">

    <h2 class="text-2xl font-semibold mb-6">{{ __('projects.edit_project') }}</h2>

    <form action="{{ route('projects.update', $project->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium">{{ __('projects.title') }}</label>
            <input type="text" name="title"
                   value="{{ $project->title }}"
                   class="w-full border rounded-lg px-3 py-2"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium">{{ __('projects.description') }}</label>
            <textarea name="description"
                      class="w-full border rounded-lg px-3 py-2">{{ $project->description }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium">{{ __('projects.assign_staff') }}</label>
            <select name="staff_id" class="w-full border rounded-lg px-3 py-2" required>
                @foreach($staffUsers as $staff)
                    <option value="{{ $staff->id }}"
                        {{ $project->staff_id == $staff->id ? 'selected' : '' }}>
                        {{ $staff->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium">{{ __('projects.status') }}</label>
            <select name="status" class="w-full border rounded-lg px-3 py-2">
                <option value="active" {{ $project->status === 'active' ? 'selected' : '' }}>
                    {{ __('projects.active') }}
                </option>
                <option value="inactive" {{ $project->status === 'inactive' ? 'selected' : '' }}>
                    {{ __('projects.inactive') }}
                </option>
            </select>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 border rounded-lg">
                {{ __('projects.cancel') }}
            </a>
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                {{ __('projects.update') }}
            </button>
        </div>
    </form>

</div>
@endrole
@endsection
