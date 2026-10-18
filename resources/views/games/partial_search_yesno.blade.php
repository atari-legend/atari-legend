{{-- One yes/no field of the search form, laid out two per row: $param, and $label --}}
<div class="col-12 col-md-6">
    <div class="row mb-3">
        <label for="{{ $param }}" class="col-5 col-sm-4 col-md-6 col-form-label">{{ $label }}</label>
        <div class="col">
            <select class="form-select" id="{{ $param }}" name="{{ $param }}">
                <option value="">-</option>
                <option value="1" @selected(request()->filled($param) && request()->boolean($param))>Yes</option>
                <option value="0" @selected(request()->filled($param) && ! request()->boolean($param))>No</option>
            </select>
        </div>
    </div>
</div>
