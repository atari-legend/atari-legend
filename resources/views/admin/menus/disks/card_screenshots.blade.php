<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Screenshots</h2>
    </div>
    @isset ($disk)
        <div class="row row-cols-2 m-2">
            @foreach ($disk->screenshots as $screenshot)
                <div class="col position-relative mb-2">
                    <form action="{{ route('admin.menus.disks.destroyScreenshot', ['disk' => $disk, 'screenshot' => $screenshot]) }}" method="POST"
                        onsubmit="javascript:return confirm('This screenshot will be removed')">
                        @csrf
                        @method('DELETE')

                        <img class="card-img-top w-100 border border-dark"
                            src="{{ asset('storage/images/menu_screenshots/'.$screenshot->file) }}"
                            alt="Screenshot of disk">

                        <button title="Remove screenshot" class="btn position-absolute end-0 pe-4">
                            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
    <form action="{{ route('admin.menus.disks.storeScreenshot', $disk) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row m-2">
            <div class="col-9">
                <input type="file" class="form-control w-100" name="screenshot">
            </div>
            <div class="col-3">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </div>
    </form>
</div>
