<div class="mb-3">
    <label for="software" class="form-label">Software</label>
    <select class="form-select @error('software') is-invalid @enderror"
        id="software" name="software">
        <option value="">-- Select --</option>
        @foreach ($softwares as $software)
            <option value="{{ $software->id }}" @if((int) old('software') === $software->id) selected @endif>{{ $software->name }}</option>
        @endforeach

    </select>

    @error('software')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
    <div class="form-text">To add & edit the software list, go to the <a href="{{ route('admin.menus.software.index') }}">Software section</a>.</div>
</div>
