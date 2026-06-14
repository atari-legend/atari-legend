@extends('admin.layouts.admin')

@section('title', 'Import menus | ' . $set->name)

@section('content')
    <div class="row">
        <div class="col">
            <div class="card mb-3 bg-light">
                <div class="card-body">
                    <h2 class="card-title fs-4">Import menus into "{{ $set->name }}"</h2>
                    <p class="text-muted">
                        Fill in the Excel template with the menus, disks and disk contents to add to this set,
                        then upload it below. You will be able to review and correct everything before anything is saved.
                    </p>
                    <a href="{{ route('admin.menus.sets.import.template', ['set' => $set]) }}" class="btn btn-outline-primary">
                        <i class="fas fa-download fa-fw"></i> Download the Excel template
                    </a>
                </div>
            </div>

            <livewire:admin.menu-import :set="$set" />
        </div>
    </div>
@endsection
