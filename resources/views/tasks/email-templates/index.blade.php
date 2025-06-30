@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Email Templates</h1>
    <a href="{{ route('email-templates.create') }}" class="btn btn-primary mb-3">Add New Template</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr><th>Name</th><th>Subject</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->subject }}</td>
                    <td>
                        <a href="{{ route('email-templates.edit', $template) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('email-templates.destroy', $template) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Delete this template?')" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
