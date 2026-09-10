<div class="field">
    <label for="member-role-{{ $fieldSuffix }}">Peran</label>
    <select class="input" id="member-role-{{ $fieldSuffix }}" name="role" required>
        <option value="VIEWER" @selected(($member?->role ?? 'VIEWER') === 'VIEWER')>Viewer</option>
        <option value="OFFICER" @selected($member?->role === 'OFFICER')>Petugas</option>
        <option value="ADMIN" @selected($member?->role === 'ADMIN')>Admin Laporan</option>
    </select>
</div>
<fieldset class="permission-grid">
    <legend>Izin rinci</legend>
    @foreach($permissions as $key => $label)
        <label><input type="checkbox" name="{{ $key }}" value="1" @checked($member?->{$key})> {{ $label }}</label>
    @endforeach
</fieldset>
