@props(['users', 'selected' => null])

<select name="team_members[]" class="form-select me-2">
    <option value="">-- Select Member --</option>
    @foreach($users as $u)
        <option value="{{ $u->id }}" {{ $u->id == $selected ? 'selected' : '' }}>{{ $u->name }}</option>
    @endforeach
</select>
