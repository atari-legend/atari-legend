<h2 class="card-title fs-4">Software</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">

        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Only use for software that is part of menu disks. If a menu contains a game,
            it should be linked to the proper game record.
        </div>

        <livewire:admin.software-table />

        <a href="{{ route('admin.menus.software.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu software
        </a>
    </div>
</div>
