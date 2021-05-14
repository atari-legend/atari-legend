<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Submit News</h2>
    </div>

    <div class="card-body p-2">
        <p class="card-text text-center">
            Did something important happen in the Atari scene that has not been
            posted yet, make sure to let us know by simply filling in this form.
            You will be credited on the front page of Atari Legend!
        </p>
    </div>
    <div class="card-body p-2">
        @guest
            <p class="card-text text-center text-danger">
                Please <a href="{{ route('login') }}">log in</a> to submit a news item
            </p>
        @endguest

        @auth
        <form method="post" action="{{ route('news.submit') }}" class="text-center">
            @csrf
            <input class="form-control mb-2" type="text" name="title" placeholder="News title">
            <textarea class="form-control" rows="5" name="text" placeholder="News text..." required></textarea>
            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </form>
        @endauth
    </div>
</div>
