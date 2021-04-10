<div class="mb-3">
    <label for="software_name" class="form-label">Software</label>
    <input class="autocomplete form-control @error('software') is-invalid @enderror"
        name="software_name" id="software_name" type="search" required
        data-autocomplete-endpoint="{{ route('ajax.software') }}"
        data-autocomplete-key="name" data-autocomplete-id="id"
        data-autocomplete-companion="software" value="{{ old('software_name') }}"
        placeholder="Type a software name..." autocomplete="off">
    <input type="hidden" name="software" value="{{ old('software') }}">

    @error('software')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
    <div class="form-text">To add & edit the software list, go to the <a href="{{ route('admin.menus.software.index') }}">Software section</a>.</div>
</div>
