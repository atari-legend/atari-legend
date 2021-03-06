<div class="card bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Disk content</h2>
        <div class="row mb-2">
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add game release or extra</a>
                <span class="text-muted">
                    Use this option for a game that is present on the menu, or to add
                    an extra (doc, hint, trainer, …) for a game that is present on the menu.
                </span>
            </p>
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add standalone game doc or extra</a>
                <span class="text-muted">
                    Use this option to add a doc, hint, trainer or other extra for a game
                    that is <strong>not</strong> present on the menu (e.g. a standalone doc or
                    trainer for a game).
                </span>
            </p>
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add software</a>
                <span class="text-muted">
                    Use this option to add a non-game software (e.g. demo, utility, …).
                </span>
            </p>
        </div>

        @isset ($disk)
            <div class="row">
                @foreach ($disk->contents->sortBy('order') as $content)
                    <div class="col-12 col-sm-6 col-md-3 col-xl-2 d-flex">
                        @include('admin.menus.disks.card_content_item')
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
