{{-- One row of the search form that can be searched on by free text with
     suggestions, or by picking from a dropdown: $param, $label, $endpoint,
     $key, $options (value => text) --}}
<div class="row mb-3">
    <label for="{{ $param }}" class="col-5 col-sm-4 col-md-3 col-form-label">
        {{ $label }}
        <a href="#" data-dropdown-toggle="{{ $param }},{{ $param }}_id" title="Pick from a list instead"><i class="fas fa-chevron-circle-down"></i></a>
    </label>
    <div class="col position-relative">
        <input type="text" class="autocomplete form-control @if (request()->filled($param . '_id')) d-none @endif"
            data-autocomplete-endpoint="{{ $endpoint }}"
            data-autocomplete-key="{{ $key }}"
            value="{{ request($param) }}"
            id="{{ $param }}" name="{{ $param }}" autocomplete="off">
        <select class="form-select @unless (request()->filled($param . '_id')) d-none @endunless" id="{{ $param }}_id" name="{{ $param }}_id">
            <option value="">-</option>
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) request($param . '_id') === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>
    </div>
</div>
