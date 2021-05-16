<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Submit Info</h2>
    </div>

    <div class="card-body p-2">
        <p class="card-text">
            Is there anything displayed on this page that is not correct or missing? Do you
            have artwork or a screenshot you like to share? Or perhaps you are one of
            the creators? Please let us know.
        </p>
    </div>

    <div class="card-body p-2">
        @guest
            <p class="card-text text-center text-danger">
                Please <a href="{{ route('login') }}">log in</a> to submit info
            </p>
        @endguest

        @auth
        <form method="post" action="{{ route('games.submitInfo', ['game' => $game]) }}" enctype="multipart/form-data" class="text-center">
            @csrf
            <textarea class="form-control mb-2" rows="5" name="info" placeholder="Your info here..." required></textarea>
            <input type="file" class="form-control bg-dark" id="files" name="files[]" multiple>

            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </form>
        @endauth
    </div>
</div>
