<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Add 'Did you know?'</h2>

        <form action="{{ route('admin.others.trivias.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="text" class="form-label visually-hidden">Text</label>
                <textarea class="form-control" id="text" name="text" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">Add</button>
        </form>
    </div>
</div>
