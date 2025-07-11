@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Company Hour</h1>

    <form action="{{ route('companyhour.update', $companyhour->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="start_at">Start Time</label>
            <input type="time" name="start_at" id="start_at" class="form-control" value="{{ $companyhour->start_at }}" required>
        </div>

        <div class="form-group mt-3">
            <label for="end_at">End Time</label>
            <input type="time" name="end_at" id="end_at" class="form-control" value="{{ $companyhour->end_at }}" required>
        </div>

        <button type="submit" class="btn btn-success mt-4">Update</button>
        <a href="{{ route('companyhour.index') }}" class="btn btn-secondary mt-4">Cancel</a>
    </form>
</div>
@endsection
