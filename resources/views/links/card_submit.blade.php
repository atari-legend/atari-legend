<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Submit Link</h2>
    </div>

    <div class="card-body p-2">
        <p class="card-text text-center">
            Do you have an interesting Atari or retro related website you would like to
            share? Please let us know.
        </p>
    </div>
    <div class="card-body p-2">
        @guest
            <p class="card-text text-center text-danger">
                Please <a href="{{ route('login') }}">log in</a> to submit a link
            </p>
        @endguest

        @auth
        <form method="post" action="{{ route('links.submit') }}" class="text-center">
            @csrf
            <input class="form-control mb-2" type="text" name="name" placeholder="e.g. 'Official Atari website'">
            <input class="form-control mb-2" type="url" name="url" placeholder="e.g. 'https://www.atari.com/'" required>
            <textarea class="form-control" rows="5" name="description" placeholder="e.g. 'Contains information about the latest Atari games'" required></textarea>
            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </form>
        @endauth
    </div>
</div>
