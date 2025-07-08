@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container">
    <h1>{{ __('template.title') }}</h1>
    <a href="{{ route('email-templates.create') }}" class="btn btn-primary mb-3">{{ __('template.add_new_template') }}</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('email-templates.index') }}" class="mb-3 d-flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or subject" class="form-control w-25">
        
        <select name="sort_by" class="form-select w-auto">
            <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Name</option>
        </select>

        <select name="sort_dir" class="form-select w-auto">
            <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>Descending</option>
            <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>Ascending</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>


    <div class="accordion" id="templateAccordion">
        @forelse($templates as $template)
            <div class="accordion-item mb-3">
                <h2 class="accordion-header" id="heading-{{ $template->id }}">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $template->id }}" aria-expanded="false" aria-controls="collapse-{{ $template->id }}">
                        {{ $template->name }} - <span class="ms-2 text-muted">{{ $template->subject }}</span>
                    </button>
                </h2>
                <div id="collapse-{{ $template->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $template->id }}" data-bs-parent="#templateAccordion">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <strong>{{ __('template.subject') }}:</strong> {{ $template->subject }}
                        </div>
                        <div class="mb-3">
                            <strong>{{ __('template.description') }}:</strong><br>
                            {!! nl2br(e($template->description ?? '')) !!}
                        </div>
                        <div class="mb-3">
                            <strong>{{ __('template.content') }}:</strong><br>
                            {!! $template->content !!}
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('email-templates.edit', $template) }}" class="btn btn-sm btn-warning">{{ __('template.edit') }}</a>
                            <form action="{{ route('email-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('{{ __('template.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('template.delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p>{{ __('template.no_templates_found') }}</p>
        @endforelse
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $templates->links() }}
    </div>
</div>
@endsection