<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Logo</h2>

        @isset ($crew->logo_file)
            <div class="text-center m-2">
                <img class="img-fluid" src="{{ asset('storage/images/crew_logos/'.$crew->logo_file) }}">
                <form action="{{ route('admin.menus.crews.destroyLogo', $crew) }}" method="POST"
                    onsubmit="javascript:return confirm('The logo will be removed')">
                    @csrf
                    @method('DELETE')

                    <button title="Remove logo" class="btn">
                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        @endif
    </div>

    <form action="{{ route('admin.menus.crews.storeLogo', $crew) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row m-2">
            <div class="col-9">
                <input type="file" class="form-control w-100" name="logo" required>
            </div>
            <div class="col-3">
                <button type="submit" class="btn btn-success w-100">
                    @if ($crew->crew_logo) Replace @else Add @endif
                </button>
            </div>
        </div>
    </form>

</div>
